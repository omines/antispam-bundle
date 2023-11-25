# Changelog
All notable changes to `omines\antispam-bundle` will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Changed
 - Update French Translation antispam+intl-icu.fr.yaml (#15) (@celinora)

## [0.1.5] - 2023-11-24
### Added
 - New `FORM_PROCESSED` event allows processing ham as well as spam
 - New `quarantine.only_spam` configuration enables also storing ham in quarantine
 - Add French translation (#14) (@Huluti)

### Changed
 - Submit Timer now uses millisecond precision and configuration (#13)

## [0.1.4] - 2023-11-17
### Added
 - Implement ResetInterface for proper adaptation to long running servers (#10)
 - Implement basic quarantine functionality
 - Add PSR-compliant application logging
 - Add basic `antispam::stats` console command

### Fixed
 - Stealth behavior in embedded forms should now be correct

## [0.1.3] - 2023-11-15
### Added
 - All caught spam is now put into a quarantine folder
 - Bundle can now be enabled/disabled globally for forms
 - Accessors added for last test result for extension purposes

### Changed
 - Stealth/passive/enabled flags should make more sense now (#4)

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

[Unreleased]: https://github.com/omines/antispam-bundle/compare/0.1.5...master
[0.1.5]: https://github.com/omines/antispam-bundle/compare/0.1.4...0.1.5
[0.1.4]: https://github.com/omines/antispam-bundle/compare/0.1.3...0.1.4
[0.1.3]: https://github.com/omines/antispam-bundle/compare/0.1.2...0.1.3
[0.1.2]: https://github.com/omines/antispam-bundle/compare/0.1.1...0.1.2
[0.1.1]: https://github.com/omines/antispam-bundle/compare/0.1.0...0.1.1
