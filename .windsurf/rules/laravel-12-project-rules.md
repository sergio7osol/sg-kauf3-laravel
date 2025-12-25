---
description: Project-specific Laravel 12 development rules for sg-kauf3-laravel workspace
scope: project
trigger: model_decision
---

# Laravel 12 Project Rules

## SOLID Principles
- **Single Responsibility**: Each class should have one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Subtypes must be substitutable for their base types
- **Interface Segregation**: Many specific interfaces over one general interface
- **Dependency Inversion**: Depend on abstractions, not concretions

## PSR Standards
- Follow PSR-12 coding style
- Use PSR-4 autoloading
- Implement PSR-3 for logging when needed

## Laravel Best Practices
- Use Eloquent ORM properly with relationships
- Implement Form Requests for validation
- Use Resource Controllers for RESTful operations
- Apply Service/Repository pattern for complex business logic
- Use Laravel's built-in features before creating custom solutions
- Implement proper exception handling
- Use database migrations and seeders
- Apply Query scopes for reusable query logic
- Use Laravel Collections for data manipulation
- Implement proper API resource transformations

## Code Quality Requirements

### Security
- Use Laravel Sanctum for SPA/API authentication
- Validate all user input with Form Requests
- Use Mass Assignment protection ($fillable or $guarded)
- Implement CSRF protection for forms
- Use parameterized queries (Eloquent/Query Builder)
- Apply proper authorization with Policies and Gates
- Sanitize output to prevent XSS

### Performance
- Use eager loading to prevent N+1 queries
- Implement caching where appropriate
- Use database indexing properly
- Apply pagination for large datasets
- Use queue jobs for time-consuming tasks
- Optimize database queries

### Testing
- Write feature tests for critical functionality
- Use database factories for test data
- Implement unit tests for business logic
- Use RefreshDatabase trait in tests

## File Organization
- Controllers: Handle HTTP requests only, delegate logic to services
- Models: Eloquent models with relationships and scopes
- Services: Business logic and complex operations
- Repositories: Optional, for complex data access patterns
- Requests: Form validation rules
- Resources: API response transformations
- Policies: Authorization logic
- Jobs: Queued background tasks
- Events/Listeners: For decoupled event-driven logic

## When Writing Code
1. Check if Laravel has a built-in solution first
2. Follow Laravel naming conventions
3. Use type hints and return types
4. Write descriptive variable and method names
5. Add PHPDoc blocks for complex methods
6. Keep methods small and focused
7. Use dependency injection over facades in classes
8. Implement proper error handling with try-catch
9. Log errors appropriately
10. Return consistent response formats for APIs

## Database
- Always use migrations for schema changes
- Use seeders for test data
- Define foreign key constraints
- Use appropriate column types
- Add indexes for frequently queried columns
- Use database transactions for multiple related operations

## API Development
- Use API Resources for response transformation
- Implement proper HTTP status codes
- Version your API routes
- Use throttling for rate limiting
- Return consistent error responses
- Document API endpoints

## Environment & Configuration
- Never commit .env files
- Use config files for application settings
- Access config via config() helper, not env()
- Use environment-specific configurations

## Before Suggesting Code Changes
- Verify compatibility with Laravel 12
- Check if the approach follows Laravel conventions
- Ensure code follows SOLID principles
- Consider performance implications
- Think about testability
- Verify security best practices are followed
