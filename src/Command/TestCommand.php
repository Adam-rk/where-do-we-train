<?php

namespace App\Command;

use App\Entity\SportPlanning;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

#[AsCommand(name: 'app:test')]
class TestCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $session = new SportPlanning();
        $session->setPromotion(['M1'])
            ->setStartingDateTime(new \DateTime('2023-06-03 09:00:00'))
            ->setEndingDateTime(new \DateTime('2023-06-03 11:00:00'));
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}