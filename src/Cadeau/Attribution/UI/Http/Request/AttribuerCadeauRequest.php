<?php

declare(strict_types=1);

namespace App\Cadeau\Attribution\UI\Http\Request;

use App\Cadeau\Attribution\Application\Command\AttribuerCadeau\AttribuerCadeauCommand;
use App\Cadeau\Attribution\Domain\ValueObject\CadeauId;
use App\Cadeau\Attribution\Domain\ValueObject\HabitantId;
use App\Shared\Infrastructure\Validator\Constraint\CadeauDisponible;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Request DTO: Attribuer Cadeau
 *
 * ✅ HEXAGONAL ARCHITECTURE - UI LAYER:
 * This Request DTO belongs to the UI layer (not Application).
 * It's responsible for HTTP validation and transformation to Application Command.
 *
 * Separation of concerns:
 * - UI Layer (this class): HTTP validation, format validation, custom validators
 * - Application Layer (Command): Pure PHP, no Symfony dependencies
 * - Domain Layer: Business rules, Value Objects
 *
 * Validation flow:
 * 1. Symfony validates this DTO (#[MapRequestPayload] in Controller)
 * 2. If valid -> toCommand() converts to pure Application Command
 * 3. Command dispatched to Handler (already validated)
 *
 * Benefits:
 * ✅ Application Command stays 100% pure (no Symfony annotations)
 * ✅ Custom validators (#[CadeauDisponible]) stay in UI/Infrastructure
 * ✅ Clear separation: UI validation vs Application logic
 * ✅ Testable: Can test Command without HTTP concerns
 */
final readonly class AttribuerCadeauRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'ID de l\'habitant est requis')]
        #[Assert\Uuid(message: 'L\'ID de l\'habitant doit être un UUID valide')]
        public string $habitantId,

        #[Assert\NotBlank(message: 'L\'ID du cadeau est requis')]
        #[Assert\Uuid(message: 'L\'ID du cadeau doit être un UUID valide')]
        #[CadeauDisponible]  // ✅ Custom validator (preliminary validation)
        public string $cadeauId,
    ) {}

    /**
     * Converts this Request DTO to a pure Application Command.
     *
     * ✅ UI -> Application boundary:
     * - Request DTO (UI): strings with validation annotations
     * - Command (Application): Value Objects, pure PHP
     *
     * @return AttribuerCadeauCommand Pure Application Command
     */
    public function toCommand(): AttribuerCadeauCommand
    {
        return new AttribuerCadeauCommand(
            habitantId: new HabitantId($this->habitantId),
            cadeauId: new CadeauId($this->cadeauId)
        );
    }
}
