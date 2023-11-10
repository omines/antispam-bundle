# Changelog
All notable changes to `omines\antispam-bundle` will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [0.1.2] - 2023-11-10
### Added
 - Add AntiSpamEvents::VALIDATOR_VIOLATION event allowing inspection or cancellation of
   spam-related violations
 - Implement passive mode on validators: will still detect, not fail

### Changed
 - Adjust default timer field to something generic (#6) (@kbond)

## [0.1.1] - 2023-11-10
### Fixed
- Quickfix for form elements all becoming TextType instead of specialized types.

## 0.1.0 - 2023-11-10
First public release.

[Unreleased]: https://github.com/omines/antispam-bundle/compare/0.1.2...master
[0.1.2]: https://github.com/omines/antispam-bundle/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/omines/antispam-bundle/compare/0.1.0...0.1.1
