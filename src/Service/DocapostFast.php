<?php

namespace BCedric\DocapostBundle\Service;

use Exception;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DocapostFast
{
    private $client;
    private $pem_file;
    private $pem_password;
    private $url;
    private $siren;
    private $circuitId;

    /**
     * DocapostFast constructor.
     * @param HttpClientInterface $client
     * @param array $parameters
     */
    public function __construct(
        HttpClientInterface $client,
        string $pem_file,
        string $pem_password,
        string $url,
        string $siren,
        string $circuitId,

    ) {
        $this->client = $client;
        $this->pem_file = $pem_file;
        $this->pem_password = $pem_password;
        $this->url = $url;
        $this->siren = $siren;
        $this->circuitId = $circuitId;
    }

    public function getSignInfo($documentiId)
    {
        $history = $this->history($documentiId);
        $numOfSign = count(array_filter($history, function ($step) {
            return $step['userFullname'] != '' && ($step['stateName'] === 'OTP signé' || $step['stateName'] === 'Visa approuvé' || $step['stateName'] === 'Signé');
        }));
        $lastStep = end($history);
        $isRefused = $lastStep != false && strpos($lastStep['stateName'], 'Refusé') !== false || $lastStep['stateName'] === 'Visa désapprouvé';
        return [
            'refusedMessage' => $isRefused ? $this->getRefusalMessage($documentiId) : [],
            'isRefused' => $isRefused,
            'nbSignatures' => $numOfSign,
        ];
    }

    public function exportUsersData()
    {
        $response = $this->sendQuery("GET", "exportUsersData?siren=" . $this->siren);
        return $response->getContent();
    }

    public function getUsers(): array
    {
        $res = json_decode($this->exportUsersData(), true);
        $res = array_map(function ($u) {
            unset($u['circuits']);
            return $u;
        }, $res['users']);
        return array_values($this->filterUsers($res));
    }

    public function getUsersCertificate(): array
    {
        $res = json_decode($this->exportUsersData(), true);
        $users = $res['users'];
        $certificat = array_filter($users, function ($u) {
            if (array_key_exists('circuits', $u)) {
                $circuit = current($u['circuits']);
                $habilitation = current($circuit['habilitations']);
                return $habilitation['profil'] === 'CERTIFICAT';
            } else {
                return false;
            }
        });
        $certificat = array_map(function ($u) {
            unset($u['circuits']);
            return $u;
        }, $certificat);
        return array_values($this->filterUsers($certificat));
    }

    private function filterUsers(array $list): array
    {
        return array_filter($list, function ($u) {
            return $u['prenom'] !== 'UCA' && $u['nom'] !== 'Test';
        });
    }

    public function delete(string $documentId)
    {
        $response = $this->sendQuery("DELETE", "documents/v2/$documentId");
        try {
            return $response->getContent();
        } catch (\Throwable $th) {
            return "errorCode : " . $th->getMessage();
        }
    }

    public function getRefusalMessage(string $documentId)
    {
        $response = $this->sendQuery("GET", "documents/v2/$documentId/comments/refusal");
        return json_decode($response->getContent(), true);
    }

    public function getFdc(string $documentId)
    {
        $response = $this->sendQuery("GET", "documents/v2/$documentId/getFdc");
        return $response->getContent();
    }

    public function getSmsUrl(string $documentId)
    {
        $response = $this->sendQuery("GET", "documents/v2/otp/url?id=$documentId");
        return json_decode($response->getContent(), true);
    }

    public function getMetas(string $documentId)
    {
        $response = $this->sendQuery("GET", "documents/$documentId/metas");
        return $response->getContent();
    }

    public function history(string $documentId)
    {
        $response = $this->sendQuery("GET", "documents/v2/$documentId/history");
        return json_decode($response->getContent(), true);
    }

    public function dynamicCircuit(string $document, array $steps, array $OTPSteps, string $emailDestinataire = "", string $circuitId = "", string $comment = "")
    {
        $circuit = [
            "type" => "BUREAUTIQUE_PDF",
            "steps" => $steps,
        ];

        $docapostId = $this->uploadOnDemand($document, $circuit, strtolower($emailDestinataire), $circuitId, $comment);
        if ((int) $docapostId === 0) {
            $json = json_decode($docapostId, true);
            throw new Exception("Erreur de l'envoi du fichier à docapost. " . $json['developerMessage'] . ". " . $json['userFriendlyMessage']);
        }
        $response = json_decode($docapostId, true);
        if (is_array($response) && $response['errorCode'] === 119) {
            throw new Exception("L'utilisateur renseigné comme visa n'a pas de compte utilisateur Docapost. Veuillez en informer un administrateur.");
        }
        $this->uploadOTPInformation($docapostId, $OTPSteps);
        return $docapostId;
    }

    public function uploadOnDemand(string $document, array $circuit, string $emailDestinataire = "", string $circuitId = "", string $comment = "")
    {
        $jsonEncoder = new JsonEncoder();

        $formFields = [
            'email_destinataire' => $emailDestinataire,
            'doc' => DataPart::fromPath($document),
            'circuit_id' => ($circuitId === '' || $circuitId === '0' ? $this->circuitId : $circuitId),
            'circuit' => $jsonEncoder->encode($circuit, 'JSON'),
        ];
        if ($comment != "") {
            $formFields['comment'] = $comment;
        }
        $formData = new FormDataPart($formFields);

        $response = $this->sendQuery("POST", "documents/ondemand/{$this->siren}/upload", [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            "body" => $formData->bodyToIterable()
        ]);

        return $response->getContent();
    }

    /**
     * @param string $document
     * @param string $label
     * @param string $comment
     * @param string $emailDestinataire
     * @param string $circuitId
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function uploadDocument(string $document, string $label, string $comment = "", string $emailDestinataire = "", string $circuitId = "")
    {
        if (mime_content_type($document) != "application/pdf") {
            throw new \Exception("Format de fichier incorrect. Veuillez envoyer un PDF.");
        }

        $formFields = [
            'label' => base64_encode($label),
            'comment' => $comment,
            'emailDestinataire' => $emailDestinataire,
            'content' => DataPart::fromPath($document),
        ];
        $formData = new FormDataPart($formFields);

        $response = $this->sendQuery("POST", "documents/v2/" . $this->siren . "/" . ($circuitId === '' || $circuitId === '0' ? $this->circuitId : $circuitId) . "/upload", [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            "body" => $formData->bodyToIterable()
        ]);
        return $response->getContent();
    }

    /**
     * @param $id
     * @return string
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function downloadDocument($id)
    {
        $response = $this->sendQuery("GET", "documents/v2/$id/download");
        return $response->getContent();
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function sendQuery(string $method, string $uri, array $parameters = [])
    {
        if (isset($_ENV['PROXY_URL'])) {
            $parameters['proxy'] = $_ENV['PROXY_URL'];
        }

        $docapost_params =  [
            "local_cert" => $this->pem_file,
            "local_pk" => $this->pem_file,
            "passphrase" => $this->pem_password,
        ];
        $url = $this->url;

        return $this->client->request(
            $method,
            $url . '/parapheur-ws/rest/v1/' . $uri,
            array_merge($parameters, $docapost_params)
        );
    }

    /**
     * @param int $documentId
     * @param array $otpInfos
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function uploadOTPInformation(int $documentId, array $otpInfos)
    {
        $xmlData = [];
        $i = 0;
        foreach ($otpInfos as $info) {
            $xmlOTP = [];
            foreach ($info as $key => $val) {
                $xmlOTP[] = ["meta-data" => ["@name" => "OTP_" . $key . "_" . $i, "@value" => $val]];
            }
            $xmlData[] = $xmlOTP;
            $i++;
        }

        $encoder = new XmlEncoder();
        $xml = $encoder->encode($xmlData, 'xml', [
            'xml_root_node_name' => 'meta-data-list'
        ]);
        $xml = preg_replace("/<\/*item[^>]*>/", "", $xml);

        $fileName = "/tmp/" . uniqid();
        file_put_contents($fileName, $xml);

        $formFields = [
            'otpinformation' => DataPart::fromPath($fileName),
        ];
        $formData = new FormDataPart($formFields);

        $parameters = [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            "body" => $formData->bodyToIterable()
        ];

        return $this->sendQuery("PUT", "documents/v2/otp/$documentId/metadata/define", $parameters);
    }
}
