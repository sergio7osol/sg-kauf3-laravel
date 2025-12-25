---
description: Project-specific architecture and API documentation for sg-kauf3-laravel workspace
scope: project
trigger: always_on
---

# Project Architecture

## Overview
- This Laravel project serves as the **backend API** for a full-stack application
- The frontend is a separate **Nuxt 4 SPA** application that consumes these API endpoints
- Authentication is handled via **Laravel Sanctum** with token-based auth for SPA compatibility
- All API routes use `auth:sanctum` middleware except the root welcome route

## API Structure

### Shops API: `/api/shops/*`
Manage stores and their addresses
- Shop addresses are nested: `/api/shops/{shop}/addresses/*`
- Supports setting primary address and toggling active status
- Full CRUD operations for shops and addresses

### Purchases API: `/api/purchases/*`
Core purchase management system
- Includes receipt file attachments: download/destroy endpoints
- Date range filtering support
- Full CRUD operations for purchases

### User Payment Methods: `/api/user-payment-methods/*`
CRUD operations for user payment methods
- Manage payment methods like credit cards, bank accounts
- Support for multiple payment types

### Receipt Processing: `/api/receipts/*`
OCR and parsing functionality
- `/api/receipts/parse` - Parse receipt text/data
- `/api/receipts/supported-shops` - Get supported shop list
- Handles receipt image/text processing

### Links API: `/api/links/*`
Resourceful link management
- Full CRUD operations for links
- Resource controller pattern

### Profiles: `/api/profiles/{id}`
User profile with associated links
- Returns user information and their links
- Public endpoint for profile viewing

## Key Features
- Purchase tracking with receipt attachments
- Shop and address management
- Receipt OCR/parsing capabilities
- User payment method management
- RESTful API design with proper HTTP methods
- Comprehensive middleware protection

## Frontend Integration Notes
- All endpoints return JSON responses
- Use proper HTTP status codes
- Frontend should handle authentication tokens via Sanctum
- Error responses should be consistent for frontend handling
- API follows RESTful conventions

## Authentication Flow
- Laravel Sanctum provides SPA-friendly authentication
- Token-based authentication for API requests
- Frontend stores and manages auth tokens
- Protected routes require valid auth token

## Data Flow
1. Frontend (Nuxt 4) makes API requests to Laravel backend
2. Laravel validates auth tokens via Sanctum middleware
3. Business logic processed in Laravel controllers/services
4. Responses returned as JSON with proper HTTP status codes
5. Frontend handles responses and updates UI accordingly
