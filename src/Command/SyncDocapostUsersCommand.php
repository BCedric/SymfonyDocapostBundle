<?php

namespace BCedric\DocapostBundle\Command;

use BCedric\DocapostBundle\Entity\DocapostUser;
use BCedric\DocapostBundle\Repository\DocapostUserRepository;
use BCedric\DocapostBundle\Service\DocapostFast;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDocapostUsersCommand extends Command
{
    public function __construct(
        private readonly DocapostFast $docapostFast,
        private readonly DocapostUserRepository $docapostUserRepository,
        private readonly EntityManagerInterface $em,

    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('docapost:sync-users')
            ->setDescription('Synchronize Docapost users')
        ;
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
