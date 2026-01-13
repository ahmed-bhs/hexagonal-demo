<?php

declare(strict_types=1);

namespace App\Tests\Integration\Cadeau\Demande\Application;

use App\Cadeau\Demande\Application\SoumettreDemandeCadeau\SoumettreDemandeCadeauCommand;
use App\Cadeau\Demande\Application\SoumettreDemandeCadeau\SoumettreDemandeCadeauCommandHandler;
use App\Cadeau\Demande\Domain\Model\DemandeCadeau;
use App\Cadeau\Demande\Domain\Port\DemandeCadeauRepositoryInterface;
use App\Shared\Domain\Validation\ValidationException;
use App\Shared\Infrastructure\Validation\SymfonyValidatorAdapter;
use App\Tests\Fake\Generator\FakeIdGenerator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * Test d'intégration pour SoumettreDemandeCadeauCommandHandler.
 *
 * Teste que le handler valide correctement les commandes avec Symfony Validator.
 */
final class SoumettreDemandeCadeauHandlerTest extends TestCase
{
    private InMemoryDemandeCadeauRepository $repository;
    private FakeIdGenerator $idGenerator;
    private SymfonyValidatorAdapter $validator;
    private SoumettreDemandeCadeauCommandHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryDemandeCadeauRepository();
        $this->idGenerator = new FakeIdGenerator();

        // Configure Symfony Validator to load YAML files
        $symfonyValidator = Validation::createValidatorBuilder()
            ->addYamlMapping(__DIR__ . '/../../../../../config/validator/demande_cadeau_command.yaml')
            ->getValidator();
        $this->validator = new SymfonyValidatorAdapter($symfonyValidator);

        $this->handler = new SoumettreDemandeCadeauCommandHandler(
            $this->idGenerator,
            $this->repository,
            $this->validator
        );
    }

    #[Test]
    public function it_submits_valid_demande_cadeau(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: 'John Doe',
            emailDemandeur: 'john@example.com',
            telephoneDemandeur: '0612345678',
            cadeauSouhaite: 'Vélo électrique',
            motivation: 'Je souhaite recevoir un vélo pour me déplacer de manière écologique.'
        );

        // Act
        $this->handler->__invoke($command);

        // Assert
        $demandes = $this->repository->findAll();
        $this->assertCount(1, $demandes);

        $demande = $demandes[0];
        $this->assertEquals('John Doe', $demande->getNomDemandeur());
        $this->assertEquals('john@example.com', $demande->getEmailDemandeur()->value);
        $this->assertEquals('0612345678', $demande->getTelephoneDemandeur());
        $this->assertEquals('Vélo électrique', $demande->getCadeauSouhaite());
    }

    #[Test]
    public function it_rejects_demande_with_invalid_email(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: 'John Doe',
            emailDemandeur: 'invalid-email',
            telephoneDemandeur: '0612345678',
            cadeauSouhaite: 'Vélo électrique',
            motivation: 'Je souhaite recevoir un vélo pour me déplacer de manière écologique.'
        );

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->handler->__invoke($command);
    }

    #[Test]
    public function it_rejects_demande_with_invalid_phone(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: 'John Doe',
            emailDemandeur: 'john@example.com',
            telephoneDemandeur: 'invalid-phone',
            cadeauSouhaite: 'Vélo électrique',
            motivation: 'Je souhaite recevoir un vélo pour me déplacer de manière écologique.'
        );

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->handler->__invoke($command);
    }

    #[Test]
    public function it_rejects_demande_with_short_name(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: 'J',
            emailDemandeur: 'john@example.com',
            telephoneDemandeur: '0612345678',
            cadeauSouhaite: 'Vélo électrique',
            motivation: 'Je souhaite recevoir un vélo pour me déplacer de manière écologique.'
        );

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->handler->__invoke($command);
    }

    #[Test]
    public function it_rejects_demande_with_short_motivation(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: 'John Doe',
            emailDemandeur: 'john@example.com',
            telephoneDemandeur: '0612345678',
            cadeauSouhaite: 'Vélo électrique',
            motivation: 'Court'
        );

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->handler->__invoke($command);
    }

    #[Test]
    public function it_rejects_demande_with_blank_fields(): void
    {
        // Arrange
        $command = new SoumettreDemandeCadeauCommand(
            nomDemandeur: '',
            emailDemandeur: '',
            telephoneDemandeur: '',
            cadeauSouhaite: '',
            motivation: ''
        );

        // Assert
        $this->expectException(ValidationException::class);

        // Act
        $this->handler->__invoke($command);
    }
}

/**
 * In-Memory Repository pour DemandeCadeau (test double).
 */
final class InMemoryDemandeCadeauRepository implements DemandeCadeauRepositoryInterface
{
    private array $demandes = [];

    public function save(DemandeCadeau $demandeCadeau): void
    {
        $this->demandes[$demandeCadeau->getId()] = $demandeCadeau;
    }

    public function find(string $id): ?DemandeCadeau
    {
        return $this->demandes[$id] ?? null;
    }

    public function findAll(): array
    {
        return array_values($this->demandes);
    }
}
