<?php

namespace BCedric\Command;

use BCedric\DocapostBundle\Service\DocapostFast;
use BCedric\Entity\DocapostUser;
use BCedric\Repository\DocapostUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('docapost:sync-users')]
class SyncDocapostUsers extends Command
{
    public function __construct(
        private readonly DocapostFast $docapostFast,
        private readonly DocapostUserRepository $docapostUserRepository,
        private readonly EntityManagerInterface $em,

    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $users = $this->docapostFast->getUsers();
        foreach ($users as $user) {
            $docapostUser = $this->docapostUserRepository->findOneByEmail($user['email']);
            if ($docapostUser == null) {
                $docapostUser = new DocapostUser();
            }
            $docapostUser->setNom($user['nom']);
            $docapostUser->setPrenom($user['prenom']);
            $docapostUser->setEmail($user['email']);

            $this->em->persist($docapostUser);
        }
        $this->em->flush();

        return Command::SUCCESS;
    }
}
