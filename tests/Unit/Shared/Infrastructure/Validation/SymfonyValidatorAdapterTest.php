<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Validation;

use App\Shared\Domain\Validation\ValidationException;
use App\Shared\Infrastructure\Validation\SymfonyValidatorAdapter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class SymfonyValidatorAdapterTest extends TestCase
{
    private SymfonyValidatorAdapter $validator;

    protected function setUp(): void
    {
        $symfonyValidator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $this->validator = new SymfonyValidatorAdapter($symfonyValidator);
    }

    #[Test]
    public function it_validates_object_with_symfony_constraints_via_attributes(): void
    {
        // Note: Utilise des attributs pour simplifier le test unitaire.
        // En production, les contraintes sont en YAML (config/validator).
        $validObject = new class ('test@example.com', 'John Doe') {
            public function __construct(
                #[Assert\NotBlank]
                #[Assert\Email]
                public string $email,

                #[Assert\NotBlank]
                #[Assert\Length(min: 2, max: 50)]
                public string $name,
            ) {
            }
        };

        $errors = $this->validator->validate($validObject);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function it_returns_validation_errors_for_invalid_object(): void
    {
        $invalidObject = new class ('invalid-email', 'A') {
            public function __construct(
                #[Assert\NotBlank]
                #[Assert\Email]
                public string $email,

                #[Assert\NotBlank]
                #[Assert\Length(min: 2, max: 50)]
                public string $name,
            ) {
            }
        };

        $errors = $this->validator->validate($invalidObject);

        $this->assertCount(2, $errors);
        $this->assertEquals('email', $errors[0]->field);
        $this->assertEquals('name', $errors[1]->field);
    }

    #[Test]
    public function it_throws_validation_exception_when_object_is_invalid(): void
    {
        $invalidObject = new class ('', '') {
            public function __construct(
                #[Assert\NotBlank(message: 'Email cannot be blank')]
                #[Assert\Email]
                public string $email,

                #[Assert\NotBlank(message: 'Name cannot be blank')]
                public string $name,
            ) {
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->validator->validateOrFail($invalidObject);
    }

    #[Test]
    public function it_validates_successfully_and_does_not_throw(): void
    {
        $validObject = new class ('test@example.com', 'John') {
            public function __construct(
                #[Assert\NotBlank]
                #[Assert\Email]
                public string $email,

                #[Assert\NotBlank]
                public string $name,
            ) {
            }
        };

        $this->validator->validateOrFail($validObject);

        $this->assertTrue(true); // No exception thrown
    }
}
