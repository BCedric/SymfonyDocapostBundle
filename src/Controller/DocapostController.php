<?php

namespace BCedric\DocapostBundle\Controller;

use BCedric\DocapostBundle\Repository\DocapostUserRepository;
use BCedric\DocapostBundle\Service\DocapostFast;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use ZipArchive;

#[Route(path: '/docapost', name: 'bcedric_docapost_')]
class DocapostController extends AbstractController
{
    protected $serializer;
    private $docapost;
    public function __construct(DocapostFast $docapost)
    {
        $this->docapost = $docapost;
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    }

    #[Route(path: '/users', name: 'users', methods: 'GET')]
    public function getUsers(DocapostUserRepository $docapostUserRepository): Response
    {
        $users = array_values(array_filter($docapostUserRepository->findAll(), fn($u) => !in_array($u->getEmail(), ['mathias.bernard@uca.fr', 'president@uca.fr'])));
        return new JsonResponse($this->serializer->normalize($users));
    }

    #[Route(path: '/certif-users', name: 'users_certif', methods: 'GET')]
    public function getUsersCertificate(DocapostUserRepository $docapostUserRepository): Response
    {
        $users = array_values(array_filter($docapostUserRepository->findAll(), fn($u) => in_array($u->getEmail(), ['president@uca.fr', 'nathalie.chantillon@uca.fr', 'sophie.fevre@uca.fr'])));
        return new JsonResponse($this->serializer->normalize($users));
    }

    #[Route(path: '/download/{docapost_id}', name: 'download', methods: 'GET')]
    public function download(string $docapost_id)
    {
        $response = $this->docapost->downloadDocument($docapost_id);
        return new Response(
            $response,
            200,
            array('Content-Type' => 'application/pdf')
        );
    }

    #[Route(path: '/getFdc/{docapost_id}', name: 'docapost_getFdc')]
    public function getFdc(string $docapost_id)
    {
        $response = $this->docapost->getFdc($docapost_id);

        return new Response(
            $response,
            200,
            array('Content-Type' => 'application/pdf')
        );
    }
    #[Route(path: '/downloadDocumentAndFDC/{docapost_id}', name: 'docapost_downloadDocument_FDC')]
    public function downloadDocumentAndFDC(
        string $docapost_id,
        #[MapQueryParameter] string $filename = ''
    ) {
        if ($filename === '') {
            return throw new Exception('You must set filename query parameter', 500);
        }
        $documentContent = $this->docapost->getArchivedDocumentData($filename);
        if ($documentContent == null) {
            $documentContent = $this->docapost->downloadDocument($docapost_id);
        }
        if ($documentContent == null) {
            return throw new Exception("File note found", 404);
        }
        $docPath = '/tmp/doc' . $docapost_id;
        $fdcPath = '/tmp/fdc' . $docapost_id;
        $resPath = '/tmp/res' . $docapost_id;
        file_put_contents($docPath, $documentContent);
        $fdcContent = $this->docapost->getFdc($docapost_id);
        file_put_contents($fdcPath, $fdcContent);
        
        $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=$resPath $docPath $fdcPath";
        exec($cmd, $output, $resultcode);

        $response = file_get_contents($resPath);
        unlink($fdcPath);
        unlink($docPath);
        unlink($resPath);
        return new Response(
            $response,
            200,
            ['Content-Type' => 'application/pdf', 'attachment; filename="' . $filename  . '"']
        );
    }

    #[Route(path: '/downloadProofFile/{docapost_id}', name: 'docapost_download_proof_file')]
    public function downloadProofFile(
        string $docapost_id,
        #[MapQueryParameter] string $filename = ''
    ) {
        if ($filename === '') {
            return throw new Exception('You must set filename query parameter', 500);
        }
        try {
            $zipContent = $this->docapost->getArchiveData($filename);
        } catch (\Throwable $th) {
            $zip = new ZipArchive();
            $docPath = '/tmp/doc' . $docapost_id;
            $fdcPath = '/tmp/fdc' . $docapost_id;
            $zipPath = '/tmp/zip' . $docapost_id . '.zip';

            $documentContent = $this->docapost->downloadDocument($docapost_id);
            file_put_contents($docPath, $documentContent);

            $fdcContent = $this->docapost->getFdc($docapost_id);
            file_put_contents($fdcPath, $fdcContent);

            if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
                exit("Impossible d'ouvrir le fichier <$zipPath>\n");
            }

            $zip->addFile($docPath, 'document.pdf');
            $zip->addFile($fdcPath, 'fdc.pdf');
            $zip->close();

            $zipContent = file_get_contents($zipPath);
            unlink($docPath);
            unlink($fdcPath);
            unlink($zipPath);
        }

        return new Response(
            $zipContent,
            200,
            [
                'Content-Type' => 'application/x-zip',
                'Content-Disposition' =>
                'attachment; filename="' . $filename  . '"'
            ]
        );
    }

    #[Route(path: '/infos/{docapost_id}', name: 'infos', methods: 'GET')]
    public function getInfos(string $docapost_id): Response
    {
        $infos = [
            'sign-infos' => $this->docapost->getSignInfo($docapost_id),
            'refusalMessage' => $this->docapost->getRefusalMessage($docapost_id),
            'smsUrl' => $this->docapost->getSmsUrl($docapost_id),
            'meta' => $this->docapost->getMetas($docapost_id),
            'history' => $this->docapost->history($docapost_id),
        ];

        return new JsonResponse($infos);
    }
}
