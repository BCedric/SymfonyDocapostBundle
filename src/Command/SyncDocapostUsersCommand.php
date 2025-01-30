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

#[AsCommand('bcedric:docapost:sync-users', 'Synchronize Docapost users')]
class SyncDocapostUsersCommand extends Command
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
        $output->writeln("======" . date('d/m/Y') . "=========");
        foreach ($users as $user) {
            $docapostUser = $this->docapostUserRepository->findOneByEmail($user['email']);
            if ($docapostUser == null) {
                $docapostUser = new DocapostUser();
                $output->writeln("Add " . $user['nom'] . " " . $user['prenom']);
            }
            $docapostUser->setNom($user['nom']);
            $docapostUser->setPrenom($user['prenom']);
            $docapostUser->setEmail($user['email']);

            $this->em->persist($docapostUser);
        }
        $this->em->flush();
        $output->writeln("=================");

        return Command::SUCCESS;
    }
}
