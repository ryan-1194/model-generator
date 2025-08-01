# CustomGenerator Console Commands Documentation

This document provides comprehensive documentation for all CustomGenerator console commands available in this Laravel application.

## Overview

The CustomGenerator package provides 8 enhanced console commands for generating Laravel components with advanced features:

1. `make:custom-model` - Create custom models with enhanced features
2. `make:custom-model-from-table` - Create models from existing database tables
3. `make:custom-migration` - Create custom migrations with enhanced features
4. `make:custom-request` - Create form requests with auto-generated validation rules
5. `make:custom-resource` - Create JSON resources with auto-generated fields
6. `make:repository` - Create repository classes and interfaces
7. `make:repository-interface` - Create repository interfaces
8. `make:cache` - Create cache classes following specific patterns

## Quick Reference

| Command | Purpose | Key Features |
|---------|---------|--------------|
| `make:custom-model` | Enhanced model generation | Interactive prompts, multiple components |
| `make:custom-model-from-table` | Model from existing table | Reads database schema automatically |
| `make:custom-migration` | Enhanced migrations | Column definitions from JSON/database |
| `make:custom-request` | Form request validation | Auto-generated validation rules |
| `make:custom-resource` | JSON API resources | Auto-generated resource fields |
| `make:repository` | Repository pattern | Creates class + interface |
| `make:repository-interface` | Repository interfaces | Standalone interface creation |
| `make:cache` | Cache classes | Model-based caching patterns |

## Common Features

- **Interactive Prompts**: Most commands use Laravel Prompts for better UX
- **Database Integration**: Commands can read existing database schemas
- **JSON Configuration**: Support for JSON-based column definitions
- **Force Override**: `--force` option available for most commands
- **Auto-generation**: Related components created automatically

## Next Steps

See individual command documentation for detailed usage instructions, examples, and options.
