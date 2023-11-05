# Contributing

Contributions are **welcome** and will be credited.

We accept contributions via Pull Requests on [Github](https://github.com/omines/antispam-bundle).
Follow [good standards](http://www.phptherightway.com/), keep the PHPstan level maxed, include tests with proper
coverage, and run `bin/prepare-commit` during development and before committing.

Infection testing is by default only done on **changed** files. It is *recommended* to run
`bin/infection` before finishing a PR to evaluate infection status of the entire project.

## Running a test environment

There is a full Symfony test project in `tests/Fixture` for functional testing. It can be run
standalone as well if you have the Symfony CLI installed for easy development:

```sh
bin/testsite
```

## Update documentation

To serve the mkdocs site in `/docs` run:

```sh
pip install mkdocs mkdocs-material mkdocs-material-extensions
bin/serve-docs
```
