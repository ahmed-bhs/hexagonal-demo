#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Cadeau\Attribution\Domain\Port\HabitantRepositoryInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $kernel = new App\Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    $kernel->boot();
    $container = $kernel->getContainer();

    $io = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());

    /** @var HabitantRepositoryInterface $repository */
    $repository = $container->get(HabitantRepositoryInterface::class);

    $habitants = $repository->findAll();

    $io->title('Test ValueObject Hydration');
    $io->section(sprintf('Found %d habitants', count($habitants)));

    foreach ($habitants as $habitant) {
        $io->writeln(sprintf(
            '- <info>%s %s</info> (age: %d, email: %s, id: %s)',
            $habitant->getPrenom(),
            $habitant->getNom(),
            $habitant->getAge()->value,  // Test Age ValueObject
            $habitant->getEmail()->value,  // Test Email ValueObject
            $habitant->getId()->value  // Test HabitantId ValueObject
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

    $io->success('ValueObjects are correctly hydrated!');

    return 0;
};
