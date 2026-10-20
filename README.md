# Laravel TestMate

Laravel TestMate is a research-oriented prototype exploring how static code analysis and Large Language Models (LLMs) can support regression testing and software maintenance in PHP applications.

The project combines PHP Abstract Syntax Tree (AST) analysis, code-diff inspection, heuristic test selection, regression risk assessment, and LLM-assisted code analysis.

## Research Question

**Can dependency-aware code analysis combined with LLM-based reasoning improve regression-test selection compared with simple name-matching heuristics, while reducing unnecessary test execution?**

Laravel TestMate is an experimental prototype. Its effectiveness has not yet been established through a formal benchmark.

## Features

- **Code Diff Analysis:** Identifies changed lines between two code versions.
- **Changed-Method Detection:** Uses PHP AST parsing to identify methods affected by code changes.
- **Change-Based Test Selection:** Recommends candidate test files using changed-method names and references found in test files.
- **Dependency-Aware Test Selection:** Identifies potential callers of changed methods and incorporates them into candidate test scoring.
- **Regression Risk Assessment:** Analyzes code changes and presents risk scores, risk levels, and AI-assisted recommendations.
- **AI-Assisted Regression Analysis:** Uses an OpenRouter-compatible LLM API to analyze code changes and provide recommendations.
- **AI Test Generation:** Generates candidate tests from supplied source code.
- **Input and API Error Handling:** Handles missing inputs, missing API configuration, empty model responses, and API errors.
- **Automated Testing:** Includes unit and feature tests covering core application workflows.

## Technology Stack

- **PHP and Laravel:** Application framework and backend implementation.
- **nikic/php-parser:** AST-based PHP code analysis.
- **sebastian/diff:** Code-diff processing.
- **OpenRouter-compatible API:** LLM-assisted regression analysis and test generation.
- **PHPUnit:** Automated unit and feature testing.

## How It Works

1. The user provides code versions for regression analysis.
2. TestMate compares the versions and identifies changed lines.
3. AST-based analysis identifies changed methods.
4. The dependency analyzer searches for potential callers of those methods.
5. The test-selection service examines application code and test files.
6. Heuristic scoring ranks candidate tests based on changed methods and potential dependency callers.
7. The application provides regression analysis, risk assessment, candidate test recommendations, and AI-generated test suggestions through the relevant workflows.

The dependency-aware analysis is lightweight and name-based. It does not construct a complete semantic dependency graph.

## Requirements

- PHP and Composer versions compatible with the project's dependencies.
- Node.js and npm if frontend assets need to be built.
- An OpenRouter API key for AI-powered features.

Check `composer.json` and `package.json` for the exact dependency and runtime requirements.

## Installation

### 1. Clone the Repository

Replace the placeholder below with the actual repository URL:

```bash
git clone <YOUR_REPOSITORY_URL>
cd laravel-testmate
composer install
```

### 2. Configure the Environment

Create your local environment file and generate the Laravel application key:

```bash
cp .env.example .env
php artisan key:generate
```

Configure the OpenRouter API key in `.env`, using the environment-variable name expected by the application.

### 3. Build Frontend Assets

If frontend dependencies are required:

```bash
npm install
npm run build
```

### 4. Start the Development Server

```bash
php artisan serve
```

Open the local URL displayed in the terminal.

**Security:** Never commit `.env` or publish API keys, passwords, or other credentials. Keep secret values out of source code and version control.

## Running Tests

Run the complete test suite:

```bash
php artisan test
```

For concise output:

```bash
php artisan test --compact
```

### Latest Verified Test Results

- **25 tests passed**
- **57 assertions**
- No failing tests in the reported run.

These results verify that the current automated tests pass. They do not establish regression-selection accuracy or performance improvements.

## Research Evaluation Plan

The next research stage should evaluate TestMate against baseline approaches, such as simple changed-method name matching and running the full test suite.

Potential evaluation metrics include:

- **Selection Ratio:** The number of selected tests divided by the total number of available tests.
- **Precision:** The proportion of selected tests that are relevant to the code change.
- **Recall:** The proportion of all relevant tests that are successfully selected.
- **Execution Time:** The time required to select and execute tests compared with running the full suite.
- **Regression Detection:** Whether the selected tests detect known or deliberately introduced regressions.

A credible evaluation requires benchmark projects, documented ground truth for relevant tests, and repeatable experiments. These metrics are proposed evaluation targets, not measured results.

## Current Limitations

- **Name-Based Dependency Matching:** Methods are matched by name, so unrelated classes using the same method name may produce false-positive caller matches.
- **Incomplete Dependency Resolution:** The analyzer does not fully resolve class identities, object types, interfaces, inheritance, or runtime dispatch.
- **Heuristic Test Selection:** Candidate tests are ranked using heuristic scoring rather than a complete semantic dependency graph.
- **LLM Reliability:** Generated analysis and tests may be inaccurate, incomplete, or unsuitable and should be reviewed by a developer.
- **No Formal Benchmark Yet:** Precision, recall, regression-detection effectiveness, and execution-time improvements have not yet been established through formal evaluation.

## Future Work

- Improve dependency resolution using class and type information.
- Compare dependency-aware selection against name-based selection and full-suite execution.
- Build benchmark datasets with documented ground-truth test relevance.
- Measure precision, recall, selection ratio, and execution time.
- Evaluate whether LLM reasoning improves test selection beyond static analysis alone.
- Investigate how the approach can support reliable AI-assisted software development and maintenance.

## Project Status

Laravel TestMate is an experimental research prototype for investigating AI-assisted software engineering, program analysis, regression testing, and software maintenance.

The current implementation provides a foundation for further development and empirical evaluation; it should not yet be considered a validated regression-testing optimization technique.

## License

See the repository's license file for the applicable license.
