# Tests Directory

This directory previously contained development test files that were removed for the production release.

## Why were test files removed?

Test files contained HTML output and debugging code that did not comply with WordPress security standards (proper output escaping). Since these files are not part of the production plugin and would never be used by end users, they were removed to ensure:

1. **Clean Production Code**: Only production-ready code in the repository
2. **Security Compliance**: 100% WordPress Coding Standards compliance
3. **Smaller Package Size**: Reduced plugin download size
4. **Best Practices**: Following WordPress plugin development best practices

## Development Testing

If you're developing or extending this plugin, you can:

1. Use WordPress unit testing framework with PHPUnit
2. Create your own test files in a local development environment
3. Use browser developer tools for frontend testing
4. Enable WordPress debugging: `define('WP_DEBUG', true);`

## Plugin Testing Checklist

### Backend Testing
- [ ] Notes CRUD operations (Create, Read, Update, Delete)
- [ ] Stage management
- [ ] User assignment
- [ ] Deadline notifications
- [ ] Audit logs
- [ ] Settings & preferences

### Frontend Testing
- [ ] Frontend notes panel display
- [ ] AJAX note creation
- [ ] Responsive design
- [ ] Browser compatibility

### Security Testing
- [ ] Nonce verification
- [ ] Capability checks
- [ ] Input sanitization
- [ ] Output escaping

For more information, see the main plugin documentation in README.md.

