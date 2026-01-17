# Multi‑Jurisdiction Tax Assistant Platform \(Laravel \+ Tailwind\)
## 1\. Problem Statement and Objectives
You need an online platform \(initially free registration\) that helps an individual manage and prepare tax\-relevant data across three jurisdictions: Spain \(current tax residence\), United States \(single‑member LLC and U\.S\. real estate in Florida\), and Colombia \(real estate and other income\)\. The system should:
* Centralize financial data from multiple sources \(YNAB, Mercury bank, spreadsheets, manual input\) across multiple currencies\.
* Map income, expenses, assets, and liabilities into tax‑relevant categories per jurisdiction and per tax year\.
* Produce clear summaries, checklists, and exports that make it easier to file required returns and informative forms \(e\.g\., Spain IRPF and Modelo 720, U\.S\. 5472\+pro‑forma 1120 and 1040/1040‑NR rental schedules, Colombian rental tax obligations\), without itself giving formal tax advice\.
* Maintain a curated, searchable library of official legal and tax documentation for these jurisdictions and evolve towards an expert system for the Spain–USA–Colombia case\.
* Be built with Laravel and Tailwind so you can maintain and extend it yourself\.
## 2\. Current Situation and Constraints
Tax situation \(high level\):
* Spain: As a Spanish tax resident, you are subject to personal income tax \(IRPF, filed via Modelo 100\) on worldwide income, and likely foreign\-asset reporting \(e\.g\., Modelo 720\) if threshold conditions are met\.
* United States: You have a single‑member U\.S\. LLC with a Mercury account and an apartment in Florida held by the LLC\. The LLC receives your main development work income, and its account is used for both business and personal spending\. You have historically filed on Form 1040; going forward, as a non‑U\.S\. resident, you anticipate:
    * Form 5472 attached to a pro‑forma Form 1120 for the foreign‑owned disregarded LLC \(informative/reporting of transactions, including owner contributions/distributions and other related‑party flows\)\.
    * Form 1040 or 1040‑NR for U\.S\.‑source real estate income and related expenses \(rents, management, insurance, taxes, etc\.\), and potentially other U\.S\.‑source income, depending on residency and elections\.
* Colombia: You hold Colombian real estate with associated rental income and expenses\. As a non‑resident, you are generally taxed on Colombian‑source income \(e\.g\., rent\), typically via withholding, but detailed obligations depend on residency thresholds and other income\.
Data and tooling today:
* YNAB: You track finances using separate budgets/files per currency and account group\.
* Spreadsheets: You have ad‑hoc spreadsheets for U\.S\. tax calculations and record‑keeping\.
* Bank/fintech: Your U\.S\. LLC operates via Mercury \(accounts and cards\), which has an API and CSV exports, and you also use Banco Santander in Spain and Bancolombia in Colombia for personal and local‑currency accounts\.
Technical constraints and preferences:
* Backend: Laravel \(latest LTS\) with first‑class API support and job queues\.
* Frontend: Tailwind CSS\-based UI using Laravel’s current official starter kit that supports Inertia and React, with a modern and polished design\.
* Hosting: Standard PHP/Laravel stack on your existing Laravel Forge–managed server, using a relational database \(PostgreSQL or MySQL\)\.
* Initial scope: Single‑user oriented \(your case\) but architected for multiple users and jurisdictions, focusing on tax years starting with 2025\.
## 3\. High‑Level Product Scope
### 3\.1 Primary Use Cases \(Initial Spain–USA–Colombia Focus\)
* Capture and normalize all relevant financial transactions across currencies, tagged with source \(LLC operations, U\.S\. rental, Colombian rental, personal living expenses, etc\.\)\.
* For each tax year and jurisdiction:
    * Compute aggregate tax‑relevant summaries \(e\.g\., gross rents, deductible expenses, depreciation placeholders, foreign‑source income totals, personal vs\. business distinctions\)\.
    * Provide mapping views that show how each transaction contributes to each jurisdiction’s tax base\.
* Provide specialized views for:
    * Spain: IRPF income breakdown by category and source; foreign asset inventory for potential Modelo 720; high‑level wealth/asset picture\.
    * U\.S\.: 
        * LLC: related‑party flows \(owner contributions/distributions, payments to you personally\) and other reportable transactions to feed 5472/1120‑style reporting\.
        * Real estate: rental income/expense ledger \(property‑level P&L\), with annual summaries compatible with 1040/1040‑NR Schedule E style data\.
    * Colombia: Property‑level rental income/expense summary in COP, clearly identifying Colombian‑source income versus other income\.
* Offer document management:
    * Store and tag invoices, contracts, bank statements, tax returns, and official guidance PDFs\.
    * Associate documents with transactions, assets, and filings\.
* Provide curated knowledge:
    * Indexed library of official guidance \(AEAT, IRS, DIAN, treaties, etc\.\) with metadata \(jurisdiction, topic, effective dates\)\.
    * Search and link from summaries to relevant legal references, while displaying clear non‑advice disclaimers\.
### 3\.2 Non‑Goals \(for Initial Iterations\)
* No automatic e‑filing to any tax authority in early versions\.
* No promise of legal/tax correctness; system is a calculation and organization helper, not a substitute for a professional\.
* No generalized multi‑tenant billing or complex subscription system at first \(free accounts only, with architecture ready for future billing\)\.
## 4\. Core Functional Requirements
### 4\.1 User and Profile Management
* Email‑based registration/login \(password auth, optional social login later\), with email verification\.
* User profile fields:
    * Personal identifiers \(name, country of residence, tax IDs as needed but stored securely and minimally\)\.
    * Residency timeline \(country and start/end dates\) to help determine which jurisdictions apply in each tax year\.
    * Configuration of default base currency \(likely EUR\) and per‑jurisdiction display currency\.
### 4\.2 Jurisdictions, Tax Years, and Filings
* Model jurisdictions \(Spain, USA, Colombia initially\) with:
    * Metadata \(ISO country code, timezone, default currency and reporting currency\)\.
    * Configurable tax year definitions \(calendar year for all three, but allow flexibility\), with initial focus on tax years from 2025 onward\.
* For each user, year, and jurisdiction, a "Filing" record containing:
    * Status \(planning, in review, filed\)\.
    * Key metrics \(e\.g\., taxable base placeholders for different income types, foreign tax credits placeholders, with manual overrides\)\.
    * Linked data sources \(transactions, assets, documents\)\.
### 4\.3 Accounts, Assets, and Transactions \(Multi‑Currency\)
* Represent entities:
    * Financial Accounts \(Mercury accounts, Banco Santander accounts, Bancolombia accounts, YNAB‑mirrored accounts, other bank/credit accounts\)\.
    * Assets \(e\.g\., Florida apartment, Colombian properties, other real estate or major assets\) with jurisdiction, ownership structure, acquisition data, and valuations\.
    * Entities \(e\.g\., U\.S\. LLC, yourself as an individual, potential future entities\) with type and jurisdiction\.
* Transactions:
    * Store raw movements with: date, description, original currency/amount, account, counterparty, and tags\.
    * Support multi‑currency via:
        * Always storing original currency and amount \(USD, EUR, COP, etc\.\)\.
        * Storing normalized amounts per jurisdiction using daily FX rates for the transaction date \(especially for Spain’s EUR reporting\), with the ability to override FX on a per‑transaction or per‑period basis\.
* Categorization:
    * User‑defined categories \(e\.g\., "US\_Rental\_Income", "US\_Rental\_Repairs", "US\_LLC\_Dev\_Income", "US\_LLC\_Opex", "US\_LLC\_Personal\_Draw", "COL\_Rental\_Taxes", "SPA\_Personal\_Employment"\)\.
    * Allow mapping of categories to jurisdiction‑specific tax concepts \(e\.g\., Spain IRPF general vs\. savings income, U\.S\. rental income and deductible rental expenses including Schedule E lines, Colombian rental categories\)\.
    * Explicitly distinguish between:
        * U\.S\. LLC operational income \(e\.g\., development work\) and its operational expenses \(relevant for Spanish tax analysis but not deductible against U\.S\. tax outside the rental context\)\.
        * Personal use of LLC funds \(draws/non‑deductible spending\)\.
        * Rental income/expenses for each property\.
* Related‑party and owner‑flow ledger:
    * Maintain a dedicated ledger for owner‑related flows, separate from the general income/expense categorization, including:
        * Owner contributions\.
        * Owner draws/distributions\.
        * Personal spending via LLC accounts/cards\.
        * Reimbursements between you and the LLC\.
    * Link ledger entries to underlying bank/card transactions but treat the ledger as the canonical view for related‑party flows, mirroring Form 5472’s focus on transactions with related parties rather than on P&L\.
### 4\.4 Integrations and Data Import
* General principles:
    * All external data pulls \(APIs, CSV imports, aggregators\) create import batches that go into a review queue before affecting tax summaries\.
    * Auto‑categorization rules provide suggestions only; you confirm or adjust them\.
* YNAB integration \(phase 1–2\):
    * Personal access token or OAuth to read budgets, accounts, and transactions via the YNAB API\.
    * Map YNAB budgets/accounts to platform accounts and import transactions, preserving payees, notes, and categories\.
    * Option for CSV import for users who prefer exports over API\.
* Aggregation via open‑finance providers \(e\.g\., Plaid or similar\):
    * Where supported, use an aggregation provider to connect to Mercury, Banco Santander, and other banks/fintechs through a unified API, feeding data into the same import‑review queue\.
    * Keep direct bank/API connections and CSV imports as fallbacks for institutions or account types not covered by the aggregator\.
* Mercury integration:
    * Read‑only access to accounts and transactions \(and later, payees and payments\) either via the Mercury API directly or via an aggregation provider where available\.
    * Support CSV import as a fallback\.
* Banco Santander \(Spain\) and Bancolombia \(Colombia\):
    * Start with robust CSV import flows for bank statements\.
    * Longer term, investigate connecting via their official/open‑banking APIs or via an aggregation provider \(for example, a PSD2‑based connection for Santander in the EU\), noting that full access may require specific regulatory or commercial onboarding\.
* Spreadsheets and generic CSV:
    * CSV import wizard with configurable column mapping \(date, amount, currency, description, account, category\)\.
    * Saved import profiles for recurring formats \(e\.g\., your existing U\.S\. tax spreadsheet exports\)\.
### 4\.5 Tax‑Specific Views and Calculations
* Spain:
    * Yearly IRPF view summarizing income by type and source \(employment, business/LLC pass‑through, rentals, investments\) in EUR, using daily FX conversions from original currencies\.
    * Clearly identify Spain‑taxable worldwide income \(including LLC‑sourced development income, U\.S\. rentals, and Colombian rentals\) versus non‑taxable flows\.
    * Highlight work‑related expenses \(including LLC operational costs\) as potential Spanish‑deductible expenses according to how you classify them with your tax advisor\.
    * Asset dashboard to track foreign assets by category for Modelo 720‑style thresholds \(bank accounts, securities, real estate\), including acquisition value, country, and currency\.
    * Reports that show:
        * Aggregated income/expense per category and per jurisdiction of origin\.
        * Asset totals by category and threshold status\.
* United States:
    * LLC operations module:
        * Track U\.S\. LLC operational income from your development work and classify spending as business vs\. personal, primarily to support Spanish tax analysis \(these expenses do not reduce U\.S\. income tax in your scenario\)\.
        * Use the dedicated owner‑flow ledger \(owner contributions, draws, personal spending, reimbursements\) as the main representation of transactions with you as a related party, so Form 5472‑style reporting can be generated directly from those flows\.
        * Summaries of inbound/outbound flows between LLC and you personally to support pro‑forma 1120 \+ Form 5472 and to separate business vs\. personal use, without computing U\.S\. income tax on development income \(only the rental activity feeds U\.S\. taxable income views\)\.
    * Real estate module:
        * Property‑level tracking of rents, repairs, taxes, insurance, management fees, travel, depreciation basis, etc\.
        * Annual P&L summaries for each property in USD, with flags for data relevant to 1040/1040‑NR Schedule E \(and explicitly marked as U\.S\.‑taxable vs\. not taxable in U\.S\. where applicable\)\.
* Colombia:
    * Rental property‑level tracking in COP\.
    * Distinguish Colombian‑source income/expenses and summarize for each tax year\.
    * Include simple logic for common withholding vs\. return‑filing scenarios \(as informational labels, not binding computations\)\.
### 4\.6 Document Management and Legal Knowledge Base
* Document management:
    * Upload PDFs/images and basic text documents \(contracts, invoices, bank statements, tax returns, official guidance\)\.
    * OCR pipeline for PDFs/images \(queued jobs\) to extract searchable text, starting with a free/self‑hosted OCR option and allowing later upgrades to external paid services if needed\.
    * Tag documents with:
        * Jurisdiction, tax year, entity/asset, document type \(e\.g\., "Official\_Guidance", "Return", "Invoice"\)\.
* Legal knowledge library:
    * Ingest official and authoritative resources \(URLs or uploaded PDFs\) for AEAT, IRS, DIAN, and relevant tax treaties, with explicit focus on the Spain–US and Spain–Colombia double taxation conventions\.
    * Maintain metadata: title, jurisdiction\(s\), treaty or domestic\-law type, topics \(e\.g\., "Spain IRPF worldwide income", "Form 5472 DE rules", "Colombia non‑resident rental", "Elimination of double taxation"\), publication date, effective period, and last‑updated timestamp\.
    * Model at a high level the mechanisms to avoid double taxation in the relevant treaties \(credit vs exemption methods, tie‑breaker and permanent establishment concepts\) so they can be referenced from reports, without treating them as binding calculations or legal conclusions\.
    * Support ongoing updates when rules or guidance change, keeping older documents and treaty versions available for reference by tax year\.
    * Full‑text search with filters \(jurisdiction, topic, date range\)\.
    * Link from tax views \(e\.g\., Spain IRPF summary\) to relevant documents and treaty articles\.
    * Display integrated disclaimers that the platform is not a tax advisor\.
### 4\.7 UX and Workflow
* Guided annual workflow per jurisdiction that can be revisited at any time during the year, not just at filing time:
    * Step 1: Confirm residency and applicable jurisdictions for the year\.
    * Step 2: Import/sync transactions and categorize \(reviewing suggested categorizations before they are finalized\)\.
    * Step 3: Define or confirm assets and entities\.
    * Step 4: Review jurisdiction‑specific summaries \(Spain, USA, Colombia\)\.
    * Step 5: Upload and link supporting documents\.
    * Step 6: Export summary package to share with your tax preparer \(PDF/CSV bundle\)\.
* Dashboards:
    * Global overview: net income and tax‑relevant metrics per jurisdiction for a selected year, updated as new data is imported\.
    * Alerts: missing data \(uncategorized transactions, properties without expenses, missing documentation for high‑value assets, potential Modelo 720 thresholds, etc\.\)\.
* UI design:
    * Modern, visually polished interface built with Tailwind CSS, leveraging your Tailwind UI / Tailwind Plus components and templates for consistent, high‑quality design\.
    * In U\.S\.‑specific views, clearly label whether each income item is "Taxable in U\.S\." or "Not taxable in U\.S\." and whether each expense "Reduces U\.S\. tax" or "Does not reduce U\.S\. tax", so non‑taxable income and non‑deductible expenses are explicitly visible in the UI\.
### 4\.8 Double Taxation Relief and Treaty Coordination
* Treaty‑aware income and tax tracking:
    * For each income stream, asset, and account, store the source jurisdiction, gross amount, and any tax withheld or paid in that jurisdiction\.
    * Highlight cases where the same income is potentially taxed by more than one country \(for example, Spain–US or Spain–Colombia situations\) and show how much tax has been paid where\.
* Use of double taxation agreements \(DTAs/DTTs\):
    * Encode high‑level rules from the Spain–US and Spain–Colombia double taxation conventions, together with Spain’s domestic foreign‑tax‑relief rules, so reports can suggest how relief is expected to work \(credit vs exemption\) without giving binding tax advice\.
    * Distinguish situations where an income tax treaty applies from those where only unilateral foreign tax credit rules apply, and flag potential residual double taxation\.
* Relief estimators \(non‑binding\):
    * Provide indicative calculations and fields for:
        * Foreign tax actually paid\.
        * Tentative credit or exemption amounts per jurisdiction and per income bucket\.
    * Always allow manual override of all computed relief amounts so they can be aligned with the final tax returns prepared and signed by a professional\.
## 5\. System Architecture and Tech Stack
### 5\.1 Backend Architecture \(Laravel\)
* Laravel \(current stable\) as the main backend framework\.
* Modular organization by bounded context:
    * Core: auth, users, profiles, organizations \(future multi‑user/multi‑entity support\)\.
    * Finance: accounts, transactions, currencies, FX rates\.
    * Tax: jurisdictions, tax years, filings, tax category mappings, summary calculations\.
    * Integrations: YNAB, Mercury, bank connectors \(Santander, Bancolombia\), CSV importers\.
    * Documents & Knowledge: document storage, OCR, knowledge metadata, search\.
* API design:
    * RESTful JSON API with versioning \(e\.g\., /api/v1/\.\.\.\)\.
    * Sanctum or Passport for API token auth for first‑party SPA/Next\.js or Inertia frontends\.
* Background processing:
    * Laravel queue \(e\.g\., Redis\) for import jobs, API sync, OCR, and heavy calculations\.
### 5\.2 Frontend
* Use Laravel’s current official starter kit that supports Inertia and React \(for example, a Breeze\-style starter if available\), with a SPA‑style architecture\.
* Styling and components:
    * Tailwind CSS as the base utility framework\.
    * Leverage your Tailwind UI / Tailwind Plus component library and templates to accelerate building a modern, beautiful interface\.
    * Reusable components for tables, filters, forms, and charts \(e\.g\., using a lightweight chart library on top of React\)\.
* Responsive design tailored for desktop use first, with an acceptable mobile experience\.
### 5\.3 Data Storage and Search
* Relational DB: PostgreSQL preferred \(JSONB support and good indexing\) but MySQL is acceptable\.
* Primary tables \(high‑level\):
    * users, profiles
    * jurisdictions, tax\_years, filings
    * entities \(e\.g\., individuals, LLCs\), assets, accounts
    * transactions, categories, transaction\_category\_assignments
    * tax\_concepts \(jurisdiction‑specific\), category\_tax\_mappings
    * documents, document\_tags, document\_links \(relation to entities/assets/transactions/filings\)
    * legal\_documents \(knowledge items\) and related metadata tables
    * integrations, integration\_connections, sync\_logs
* Search/indexing:
    * Pluggable search layer with Algolia as the preferred external search engine for documents, legal\_documents, and possibly transactions\.
    * Database full‑text search \(e\.g\., PostgreSQL FTS\) as a fallback when Algolia is not configured or for low‑volume queries\.
### 5\.4 Security, Privacy, and Compliance Considerations
* Sensitive data:
    * Encrypt at rest for API tokens \(YNAB, Mercury\) and any stored tax IDs\.
    * Use environment variables and secret management for API keys\.
* Access control:
    * Strict per‑user data isolation; no cross‑user access in early versions\.
* Audit trails:
    * Log important actions \(logins, imports, changes to residency profile, asset creation/modification, document uploads\)\.
* Legal UX:
    * Prominent disclaimers that the app is an organizational and calculation tool, not professional tax advice\.
    * Clear indication of last update date for each legal document\.
## 6\. Implementation Phases
### Phase 0 – Project Setup
* Initialize the Laravel project with a Tailwind‑based starter kit using Inertia and React \(based on Laravel’s current official offering\), wired for SPA‑style development\.
* Configure database, queues, authentication \(email verification\), and deployment to your existing Laravel Forge server\.
* Establish basic domain models for User, Jurisdiction, TaxYear, Account, Transaction, Asset, Entity, and initial relationships\.
* Add Laravel factories and seeders to generate realistic sample data representing your Spain–USA–Colombia situation for the 2025 tax year\.
### Phase 1 – Personal MVP Focused on Your Spain–USA–Colombia Scenario \(Starting 2025\)
* Implement for 2025 and forward:
    * User profile with residency timeline and jurisdiction settings \(including Spain tax residence start\)\.
    * Manual account and transaction creation, plus CSV import for:
        * YNAB exports\.
        * Mercury exports\.
        * Banco Santander and Bancolombia bank statement exports\.
    * Import review queue and basic auto‑categorization suggestions that you can accept or override transaction by transaction\.
    * Category system and initial Spain/USA/Colombia tax category mappings for your specific needs:
        * Spain IRPF income buckets with daily FX conversions to EUR and work‑related expense categories oriented to Spanish deductibility\.
        * U\.S\. LLC operational income \(development work\), rental P&L, and related‑party flows, with expense categorization focused on business vs\. personal use and on supporting Spanish tax treatment rather than U\.S\. income tax calculations \(beyond rental activity\)\.
        * Colombian rental buckets in COP\.
    * Spain IRPF/yearly income summary view and basic Modelo 720‑style asset dashboard\.
    * U\.S\. modules for: LLC operational flows \(5472‑style summary\) and property P&L \(1040/1040‑NR‑style summary\)\.
    * Colombia rental summary view\.
    * Document upload and basic tagging \(jurisdiction, year, asset/entity\), with optional OCR via a free/self‑hosted engine\.
    * Export of yearly jurisdiction summaries and supporting CSVs/PDFs for sharing with tax advisors at any point in the year\.
### Phase 2 – Integrations and Knowledge Base
* Integrations:
    * Add YNAB API sync \(read‑only\) with on‑demand manual refresh and optional scheduled background updates\.
    * Add Mercury API sync \(read‑only\) for transactions and balances, also reviewed via the import queue\.
    * Investigate and, where reasonable, integrate bank APIs:
        * Banco Santander via available PSD2/open‑banking endpoints through a TPP/aggregator or direct access if feasible\.
        * Bancolombia via its published APIs, subject to access requirements\.
* Knowledge base and search:
    * Models and UI for ingesting and managing official tax/legal documents with metadata and versioning by effective date\.
    * OCR pipeline and search for documents and legal\_documents, using Algolia as the preferred search engine with a database FTS fallback\.
    * Contextual links from tax summaries to relevant legal documents\.
### Phase 3 – Expert Features and Generalization
* Rule engine for tax mappings:
    * Define reusable rules per jurisdiction \(e\.g\., if transaction has tag X and account Y, map to concept Z\), still surfaced through a human review step before locking in\.
* Multi‑user and multi‑entity enhancements:
    * Support multiple individuals/entities under one user account \(e\.g\., more LLCs, family members\)\.
    * Prepare for subscription/billing \(though still optional\)\.
* Rich dashboards:
    * Cross‑jurisdiction cash‑flow and tax‑base analytics updated continuously as data changes\.
    * More sophisticated alerts \(e\.g\., approaching Modelo 720 thresholds, significant changes in income patterns\)\.
* Optional AI‑assisted features \(later\):
    * Natural‑language Q&A over your own data and the curated legal library\.
    * Semi‑automated categorization suggestions for new transactions, subject to your review\.
## 7\. Open Questions and Design Decisions to Clarify
* Preferred authoritative FX rate source for daily conversions \(e\.g\., ECB vs\. another provider\) and any Spain‑specific rules for tax reporting that we should encode\.
* How far to go with direct bank API integrations for Banco Santander and Bancolombia \(vs\. remaining on CSV imports\) in the near term\.
* Which initial subset of official documentation \(AEAT, IRS, DIAN, treaties\) you want prioritized for ingestion and tagging, given that the system supports continuous updates over time\.
