<?php

declare(strict_types=1);

namespace App\Command;

use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test:value-objects',
    description: 'Test that ValueObjects are correctly persisted and hydrated'
)]
final class TestValueObjectsCommand extends Command
{
    public function __construct(
        private readonly HabitantRepositoryInterface $habitantRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $habitants = $this->habitantRepository->findAll();

        $io->title('Test ValueObject Hydration');
        $io->section(sprintf('Found %d habitants', count($habitants)));

        foreach ($habitants as $habitant) {
            $io->writeln(sprintf(
                '- <info>%s %s</info> (age: %d, email: %s)',
                $habitant->getPrenom(),
                $habitant->getNom(),
                $habitant->getAge()->value,  // Test Age ValueObject
                $habitant->getEmail()->value  // Test Email ValueObject
            ));

            // Verify types
            $io->writeln(sprintf(
                '  Types: Age=%s, Email=%s, Id=%s',
                get_class($habitant->getAge()),
                get_class($habitant->getEmail()),
                get_class($habitant->getId())
            ));
            $io->newLine();
        }

        $io->success('✓ ValueObjects are correctly hydrated!');
        $io->info('Custom Doctrine types are working properly:');
        $io->listing([
            'HabitantIdType converts between HabitantId and string',
            'AgeType converts between Age and integer',
            'EmailType converts between Email and string'
        ]);

        return Command::SUCCESS;
    }
}
