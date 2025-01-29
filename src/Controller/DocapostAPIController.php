<?php

namespace BCedric\DocapostBundle\Controller;

use BCedric\DocapostBundle\Repository\DocapostUserRepository;
use BCedric\DocapostBundle\Service\DocapostFast;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/docapost')]
class DocapostController extends AbstractController
{

    private $docapost;
    public function __construct(DocapostFast $docapost)
    {
        $this->docapost = $docapost;
    }

    #[Route(path: '/users', name: 'docapost_api_users', methods: 'GET')]
    public function getUsers(DocapostUserRepository $docapostUserRepository): Response
    {
        $users = array_filter($docapostUserRepository->findAll(), fn($u) => !in_array($u->getEmail(), ['mathias.bernard@uca.fr', 'president@uca.fr']));
        return new JsonResponse($users);
    }

    #[Route(path: '/certif-users', name: 'docapost_api_users_certif', methods: 'GET')]
    public function getUsersCertificate(DocapostUserRepository $docapostUserRepository): Response
    {
        $users = array_filter($docapostUserRepository->findAll(), fn($u) => in_array($u->getEmail(), ['president@uca.fr', 'nathalie.chantillon@uca.fr', 'sophie.fevre@uca.fr']));
        return new JsonResponse($users);
    }

    #[Route(path: '/download/{docapost_id}', name: 'docapost_api_download', methods: 'GET')]
    public function download(string $docapost_id)
    {
        $response = $this->docapost->downloadDocument($docapost_id);
        return new Response(
            $response,
            200,
            array('Content-Type' => 'application/pdf')
        );
    }

    #[Route(path: '/infos/{docapost_id}', name: 'docapost_api_infos', methods: 'GET')]
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
