# Laravel TestMate

Laravel TestMate is a research-oriented prototype exploring how lightweight PHP code analysis and Large Language Models (LLMs) can support regression-test selection and PHPUnit test generation.

The application combines PHP Abstract Syntax Tree (AST) analysis, code-change identification, heuristic test recommendations, dependency-caller analysis, and OpenRouter-powered AI features. It provides a practical foundation for investigating AI-assisted software engineering and intelligent software maintenance.

**Repository:** https://github.com/shahid807/laravel-testmate

## Features

### 1. Regression Testing

- **Code comparison:** Compare original and updated PHP source code using a unified diff.
- **Changed-method identification:** Parse PHP code into an AST and identify added, removed, or modified methods and named functions by comparing their printed definitions.
- **Heuristic test selection:** Recommend existing PHPUnit test files using weighted matches against test filenames and file contents.
- **Dependency-caller analysis:** Parse PHP source files under the application's `app` directory and identify potential callers of changed methods using name-based matching.
- **AI regression-risk analysis:** Submit code changes for OpenRouter-powered analysis and display an estimated risk score and explanation.
- **Error handling:** Handle supported API errors, missing configuration, and network failures with user-facing feedback.

Test recommendations are ranked using heuristic scores. They do not prove that a test depends on changed code or guarantee that all affected tests will be selected.

### 2. AI-Assisted PHPUnit Test Generation

- Submit PHP source code for AI-assisted PHPUnit test generation.
- Send requests to an OpenRouter model configured through environment variables.
- Display the generated test code for developer review.
- Handle missing API configuration, invalid input, unsuccessful API responses, empty model responses, and network failures.

**Important:** Generated tests are suggestions. The application does not automatically execute generated test code in an isolated environment or guarantee that the output is correct, complete, secure, or passing.

### 3. Automated Tests

The project includes PHPUnit tests covering application pages, regression-analysis requests, test-generation requests, and error-handling scenarios.

The latest recorded full-suite run completed successfully:

| Metric | Result |
|---|---:|
| Tests | 32 |
| Assertions | 73 |
| Result | Passed |

Run the test suite again to verify the current state of your checkout.

## Technology Stack

- PHP 8.4
- Laravel 13
- PHPUnit 12
- `nikic/php-parser` for PHP AST parsing
- Sebastian Bergmann's Diff component for code diffs
- OpenRouter API for LLM-based analysis and test generation
- Blade for the web interface
- SQLite as the default database connection in `.env.example`

## How It Works

### Regression-Testing Workflow

1. The user provides the original and updated PHP code.
2. The application generates a unified diff to display the changes.
3. The application parses the supplied source and compares method and named-function definitions to identify changes.
4. The test-selection service searches for potential callers in PHP files under the application's `app` directory.
5. It scans files under `tests` whose filenames end in `Test.php`.
6. It calculates heuristic scores based on matches between changed method or caller names and test filenames or file contents.
7. Matching test files are returned as ranked recommendations.
8. The application can request an AI-based regression-risk assessment through OpenRouter.

The results help developers investigate potentially relevant tests. They do not constitute a complete dependency analysis or guarantee that all tests affected by a change have been identified.

### AI Test-Generation Workflow

1. The user submits PHP source code.
2. The application sends a request to the configured OpenRouter model.
3. The model generates proposed PHPUnit test code.
4. The application displays the response for review and possible manual execution.

Generated output should be reviewed and tested against the actual application before being relied upon.

## Research Motivation

Regression testing can become expensive as a software project grows. Running the entire test suite after every code change may consume unnecessary time, while selecting only a subset of tests risks missing regressions.

Method-name matching offers a lightweight baseline for recommending tests. AST-based caller analysis may provide additional information, while LLMs may help assess code changes and suggest test cases.

However, name-based analysis can miss indirect dependencies, and LLM-generated explanations or risk assessments may be inaccurate. These approaches therefore require empirical evaluation.

### Research Question

Can lightweight PHP code analysis combined with LLM-based reasoning improve regression-test selection compared with simple method-name matching, while reducing the number of tests that need to be executed?

Laravel TestMate provides a prototype for exploring this question. The effectiveness of the approach has not yet been established through a controlled evaluation.

## Research Evaluation Plan

The following metrics could be used in future experiments:

- **Precision:** The proportion of selected tests that are genuinely relevant to a code change.
- **Recall:** The proportion of genuinely relevant tests that are selected.
- **Test-selection ratio:** The proportion of the complete test suite recommended for execution.
- **Execution time:** The time required for test selection and execution.
- **Regression detection:** Whether selected tests detect known regressions.

A controlled evaluation would compare the existing heuristic approach with alternative selection methods using representative code changes, a known set of affected tests, and repeatable experiments.

**Evaluation status:** These are proposed research metrics, not completed benchmark results. No claim is made that the current approach has already improved precision, recall, or execution time.

## Current Limitations

- **Name-based dependency analysis:** Caller matching is based on method or function names rather than complete symbol resolution.
- **No complete dependency graph:** The analyzer does not resolve variable types or build a complete inter-file dependency graph.
- **Source parsing limitations:** Invalid or incomplete PHP source may prevent the analyzer from identifying changed methods or callers.
- **Name collisions:** Methods with identical names in different classes may be difficult to distinguish reliably.
- **Heuristic test selection:** Recommendations may include irrelevant tests or miss indirectly affected tests.
- **Limited analysis scope:** Caller discovery scans PHP files under `app`; it is not a complete analysis of every dependency in the project.
- **LLM reliability:** AI explanations, generated tests, and risk scores may be inaccurate. Risk scores should be treated as estimates, not calibrated probabilities.
- **No automatic execution of generated tests:** Generated PHPUnit code must be reviewed and run separately.
- **No formal benchmark yet:** The effectiveness of the combined approach has not been established through a controlled study.

## Installation

### Requirements

- PHP compatible with the project's Composer dependencies; development has been tested with PHP 8.4.
- Composer.
- A supported local environment for running Laravel.
- An OpenRouter API key for AI-powered features.

### 1. Clone the Repository

```bash
git clone https://github.com/shahid807/laravel-testmate.git
cd laravel-testmate
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure the Environment

Create your local environment file.

**Windows PowerShell:**

```powershell
Copy-Item .env.example .env
```

**Linux or macOS:**

```bash
cp .env.example .env
```

Generate the Laravel application key:

```bash
php artisan key:generate
```

### 4. Configure the Database

The example environment uses SQLite:

```dotenv
DB_CONNECTION=sqlite
```

Laravel installations commonly require the SQLite database file to exist before migrations can run. If `database/database.sqlite` does not exist, create it.

**Windows PowerShell:**

```powershell
New-Item -ItemType File -Path database/database.sqlite
```

**Linux or macOS:**

```bash
touch database/database.sqlite
```

The example environment also configures database-backed sessions, cache, and queues. Run the migrations to create the application's configured database tables:

```bash
php artisan migrate
```

If you choose a different database, configure the corresponding `DB_*` environment variables and ensure that the database is available before running migrations.

### 5. Configure OpenRouter

The project uses these environment variables, as defined in `.env.example` and `config/services.php`:

```dotenv
OPENROUTER_API_KEY=
OPENROUTER_MODEL=openrouter/free
```

Add your own API key to `.env`. The default model identifier is `openrouter/free`; you can change it to a model supported by OpenRouter.

Keep your API key private. Do not commit real credentials to the repository.

### 6. Run the Application

```bash
php artisan serve
```

Open the local URL displayed in the terminal.

Use the application interface to access regression testing and AI-assisted test generation.

## Running the Tests

From the project root, run PHPUnit directly:

**Windows PowerShell:**

```powershell
.\vendor\bin\phpunit
```

Alternatively, use Laravel's test command:

```bash
php artisan test
```

The latest recorded full-suite run completed with **32 tests and 73 assertions**. Results may differ if the code or dependencies have changed.

## Intended Use

Laravel TestMate is intended for:

- Exploring AI-assisted software engineering workflows.
- Prototyping code-change analysis and regression-test recommendations.
- Investigating lightweight PHP AST-based caller analysis.
- Experimenting with LLM-generated PHPUnit tests.
- Developing a foundation for future empirical research in intelligent software maintenance.

It is a research prototype, not a replacement for a complete static-analysis platform, a guaranteed test-selection engine, or a developer's review and execution of tests.

## Future Research Directions

Potential areas for further investigation include:

- Improving dependency resolution across classes and files.
- Handling method-name collisions and indirect dependencies.
- Comparing heuristic test selection with more comprehensive dependency analysis.
- Evaluating LLM-assisted test selection against a labelled set of affected tests.
- Measuring precision, recall, test-selection ratio, execution time, and regression detection.
- Assessing the correctness and usefulness of AI-generated PHPUnit tests.
- Comparing the approaches across multiple PHP projects and representative code changes.

These are proposed research directions, not claims that the corresponding features have already been implemented.

## License

Check the repository for the applicable license before redistributing or reusing this project.
