# Domain Layer

The domain layer contains pure business logic with zero dependencies on frameworks.

See [Architecture Overview](overview.md) for details.

## Entities

- **Habitant** - Resident aggregate root
- **Cadeau** - Gift entity
- **Attribution** - Attribution relationship

## Value Objects

- **HabitantId** - UUID identifier
- **Age** - Age with validation and category helpers
- **Email** - Email with validation

## Ports

Repository interfaces defining contracts for data access.
