# Contributing to Laravel P-CAPTCHA

Thank you for considering contributing to Laravel P-CAPTCHA! This document outlines how you can help improve the package.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Pull Request Process](#pull-request-process)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Documentation](#documentation)
- [Bug Reports](#bug-reports)
- [Feature Requests](#feature-requests)

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork locally
3. Create a new branch for your feature or bug fix
4. Make your changes
5. Submit a pull request

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 9.0 or higher
- Node.js (for frontend assets)

### Installation

1. Clone your fork:
```bash
git clone https://github.com/your-username/laravel-p-captcha.git
cd laravel-p-captcha
```

2. Install dependencies:
```bash
composer install
```

3. Create a test Laravel application:
```bash
composer create-project laravel/laravel test-app
cd test-app
```

4. Add your local package to the test app's composer.json:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../"
        }
    ],
    "require": {
        "core45/laravel-p-captcha": "*"
    }
}
```

5. Install the package in test app:
```bash
composer require core45/laravel-p-captcha
php artisan p-captcha:install
```

## How to Contribute

### Types of Contributions

We welcome the following types of contributions:

- **Bug fixes**: Fix issues in the existing code
- **Feature additions**: Add new CAPTCHA challenge types or functionality
- **Documentation**: Improve or add documentation
- **Tests**: Add or improve test coverage
- **Performance improvements**: Optimize existing code
- **Security improvements**: Enhance security features

### Areas We Need Help With

1. **New Challenge Types**: Create innovative CAPTCHA challenges
2. **Accessibility**: Improve accessibility features
3. **Mobile Optimization**: Enhance mobile user experience
4. **Internationalization**: Add multi-language support
5. **Performance**: Cache optimization and performance improvements
6. **Security**: Security audits and improvements

## Pull Request Process

1. **Create an Issue**: For significant changes, create an issue first to discuss the change
2. **Branch**: Create a feature branch from `main`
3. **Code**: Implement your changes following our coding standards
4. **Test**: Add tests for your changes and ensure all tests pass
5. **Document**: Update documentation if needed
6. **Commit**: Use clear, descriptive commit messages
7. **Push**: Push your branch to your fork
8. **PR**: Create a pull request with a clear description

### Pull Request Guidelines

- Use a clear and descriptive title
- Include a detailed description of changes
- Reference any related issues
- Include screenshots for UI changes
- Ensure all tests pass
- Update documentation as needed

## Coding Standards

### PHP Standards

We follow PSR-12 coding standards with some additions:

```php
<?php

namespace Core45\LaravelPCaptcha\Something;

use Illuminate\Support\Facades\Cache;
use AnotherNamespace\AnotherClass;

class ExampleClass
{
    /**
     * Method description
     * 
     * @param string $parameter
     * @return bool
     */
    public function exampleMethod(string $parameter): bool
    {
        // Implementation
        return true;
    }
}
```

### JavaScript Standards

- Use ES6+ features
- Follow ESLint rules
- Use meaningful variable names
- Add JSDoc comments for functions

```javascript
/**
 * Validate CAPTCHA solution
 * @param {string} challengeId - The challenge ID
 * @param {Object} solution - The solution object
 * @returns {Promise<boolean>} Validation result
 */
async function validateSolution(challengeId, solution) {
    // Implementation
}
```

### CSS Standards

- Use BEM methodology for class naming
- Follow mobile-first approach
- Use CSS custom properties for theming
- Keep specificity low

```css
/* Good */
.p-captcha-container {
    --primary-color: #6d4aff;
}

.p-captcha-container__header {
    color: var(--primary-color);
}

/* Avoid */
div.p-captcha-container div.header {
    color: #6d4aff;
}
```

## Testing

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/Unit/PCaptchaServiceTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage
```

### Writing Tests

- Write tests for all new functionality
- Include both positive and negative test cases
- Test edge cases and error conditions
- Use descriptive test method names

```php
/** @test */
public function it_validates_correct_beam_alignment_solution()
{
    // Arrange
    $service = new PCaptchaService();
    $challenge = $service->generateChallenge();
    
    // Act
    $result = $service->validateSolution($challenge['id'], $challenge['solution']);
    
    // Assert
    $this->assertTrue($result);
}
```

### Test Categories

- **Unit Tests**: Test individual classes and methods
- **Integration Tests**: Test component interactions
- **Feature Tests**: Test complete features end-to-end
- **Browser Tests**: Test JavaScript functionality

## Documentation

### Types of Documentation

1. **Code Comments**: Document complex logic
2. **README**: Keep the main README updated
3. **API Documentation**: Document public methods
4. **Examples**: Provide usage examples
5. **Configuration**: Document all config options

### Documentation Standards

- Use clear, concise language
- Provide code examples
- Include expected outputs
- Update docs with code changes

## Bug Reports

When reporting bugs, please include:

### Required Information

- **Laravel version**
- **PHP version**
- **Package version**
- **Operating system**
- **Browser (for frontend issues)**

### Bug Report Template

```markdown
## Bug Description
Clear description of the bug

## Expected Behavior
What you expected to happen

## Actual Behavior
What actually happened

## Steps to Reproduce
1. Step one
2. Step two
3. Step three

## Environment
- Laravel version: 10.x
- PHP version: 8.2
- Package version: 1.0.0
- OS: Ubuntu 22.04
- Browser: Chrome 118

## Additional Context
Any additional information, screenshots, or logs
```

## Feature Requests

### Feature Request Template

```markdown
## Feature Description
Clear description of the proposed feature

## Use Case
Why this feature would be useful

## Proposed Implementation
How you think it should work

## Alternatives Considered
Other solutions you've considered

## Additional Context
Any additional information or mockups
```

### Evaluation Criteria

Features are evaluated based on:

- **Usefulness**: Benefit to users
- **Complexity**: Implementation difficulty
- **Maintenance**: Long-term maintenance burden
- **Security**: Security implications
- **Performance**: Performance impact

## Security

### Reporting Security Issues

**Do not report security vulnerabilities through public GitHub issues.**

Instead, email us at: security@yourpackage.com

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

### Security Guidelines

When contributing:

- Never expose sensitive data in logs
- Validate all user inputs
- Use secure random number generation
- Follow OWASP guidelines
- Consider timing attacks

## Development Workflow

### Git Workflow

1. **Fork** the repository
2. **Clone** your fork
3. **Branch** from `main`
4. **Commit** changes with clear messages
5. **Push** to your fork
6. **PR** to main repository

### Commit Messages

Use conventional commits format:

```
type(scope): description

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

Examples:
```
feat(captcha): add new pattern matching challenge
fix(middleware): handle missing CSRF token gracefully
docs(readme): update installation instructions
```

### Release Process

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create release tag
4. Update documentation
5. Announce release

## Questions?

If you have questions about contributing:

1. Check existing issues and discussions
2. Create a new discussion on GitHub
3. Join our Discord server (if available)
4. Email us at: contribute@yourpackage.com

Thank you for contributing to Laravel P-CAPTCHA!
