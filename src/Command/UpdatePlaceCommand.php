<?php

namespace App\Command;

use App\Entity\SportPlanning;
use App\Repository\SportPlanningRepository;
use App\Service\Session;
use App\Service\WeatherApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-place',
    description: 'Add a short description for your command',
)]
class UpdatePlaceCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private Session $session)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repository = $this->entityManager->getRepository(SportPlanning::class);

        $sportSessions = $repository->getLessThanADay();

        if (empty($sportSessions)) {
            $io->error("Rien n'a été modifié");
            return Command::FAILURE;
        }
        foreach ($sportSessions as $sportSession) {
            $this->session->setPlace($sportSession);
            $io->success("Le lieu a été ajouté avec succès pour la session ".implode(', ',$sportSession->getPromotion()).": ".$sportSession->getStartingDateTime()->format('Y-m-d H:i')." - ".$sportSession->getEndingDateTime()->format('Y-m-d H:i')." ".$sportSession->getPlace());
        }



        return Command::SUCCESS;
    }
}
