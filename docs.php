<?php
$page_title       = 'API Docs — Darn Fine Surveys';
$page_description = 'REST API documentation for Darn Fine Surveys. Create surveys programmatically with a simple JSON API.';
$header_cta       = true;
include __DIR__ . '/components/header.php';
?>

<main>
    <div class="docs-hero" style="animation: fade-up 0.45s ease both;">
        <div class="hero-eyebrow" style="margin-bottom: 1.5rem;">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="16 18 22 12 16 6"/>
                <polyline points="8 6 2 12 8 18"/>
            </svg>
            REST API
        </div>
        <h1 style="font-family: var(--font-display); font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 600; letter-spacing: -0.02em; line-height: 1.15; margin-bottom: 0.85rem;">
            Build with <em style="font-style: italic; color: var(--accent);">surveys.</em>
        </h1>
        <p style="font-size: 1.05rem; color: var(--muted); max-width: 500px; line-height: 1.7; margin: 0 auto 2.5rem auto;">
            A simple, public JSON API for creating surveys programmatically. No authentication required.
        </p>
    </div>

    <div class="docs-layout" style="animation: fade-up 0.45s 0.08s ease both;">

        <!-- Sidebar nav -->
        <nav class="docs-nav">
            <div class="docs-nav-label">Endpoints</div>
            <a href="#post-surveys" class="docs-nav-link">POST /api/surveys</a>
        </nav>

        <!-- Content -->
        <div class="docs-content">

            <!-- Base URL -->
            <div class="form-card docs-section" style="margin-bottom: 1.5rem;">
                <h2 class="docs-section-title">Overview</h2>
                <p class="docs-prose">All API endpoints accept and return JSON. No authentication is required.</p>
                <div class="docs-code-block">
                    <span class="docs-code-label">Base URL</span>
                    <code>/api</code>
                </div>
                <p class="docs-prose" style="margin-top: 1rem; margin-bottom: 0;">
                    Errors return an <code class="inline-code">error</code> string (single errors) or an <code class="inline-code">errors</code> array (validation failures), along with an appropriate HTTP status code.
                </p>
            </div>

            <!-- POST /api/surveys -->
            <div class="form-card docs-section" id="post-surveys">
                <div class="docs-endpoint-header">
                    <span class="docs-method docs-method-post">POST</span>
                    <span class="docs-path">/api/surveys</span>
                </div>
                <p class="docs-prose">Create a new survey. Returns the survey ID and URL.</p>

                <div class="form-divider"></div>

                <h3 class="docs-subsection-title">Request body</h3>
                <div class="docs-table-wrap">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code class="inline-code">title</code></td>
                                <td>string</td>
                                <td>Yes</td>
                                <td>Survey title. Max 255 characters.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">expiration_days</code></td>
                                <td>integer</td>
                                <td>Yes</td>
                                <td>How long the survey stays live. Must be <code class="inline-code">1</code>, <code class="inline-code">7</code>, or <code class="inline-code">31</code>.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">questions</code></td>
                                <td>array</td>
                                <td>Yes</td>
                                <td>At least one question object (see below).</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">show_on_home</code></td>
                                <td>boolean</td>
                                <td>No</td>
                                <td>Feature the survey on the homepage. Defaults to <code class="inline-code">false</code>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="docs-subsection-title" style="margin-top: 1.5rem;">Question object</h3>
                <div class="docs-table-wrap">
                    <table class="docs-table">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code class="inline-code">label</code></td>
                                <td>string</td>
                                <td>Yes</td>
                                <td>The question text. Max 255 characters.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">type</code></td>
                                <td>string</td>
                                <td>Yes</td>
                                <td>One of <code class="inline-code">radio</code>, <code class="inline-code">checkbox</code>, <code class="inline-code">select</code>, <code class="inline-code">text_short</code>, <code class="inline-code">text_long</code>.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">choices</code></td>
                                <td>string[]</td>
                                <td>Conditional</td>
                                <td>Required for <code class="inline-code">radio</code>, <code class="inline-code">checkbox</code>, and <code class="inline-code">select</code>. At least 2 non-empty strings.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">description</code></td>
                                <td>string</td>
                                <td>No</td>
                                <td>Optional helper text shown below the question label.</td>
                            </tr>
                            <tr>
                                <td><code class="inline-code">required</code></td>
                                <td>boolean</td>
                                <td>No</td>
                                <td>Whether an answer is required. Defaults to <code class="inline-code">false</code>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-divider"></div>

                <h3 class="docs-subsection-title">Question types</h3>
                <div class="docs-table-wrap">
                    <table class="docs-table">
                        <thead>
                            <tr><th>Type</th><th>Description</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code class="inline-code">radio</code></td><td>Pick one answer from a list of radio buttons.</td></tr>
                            <tr><td><code class="inline-code">select</code></td><td>Pick one answer from a dropdown menu.</td></tr>
                            <tr><td><code class="inline-code">checkbox</code></td><td>Pick one or more answers from a list of checkboxes.</td></tr>
                            <tr><td><code class="inline-code">text_short</code></td><td>Single-line free text input.</td></tr>
                            <tr><td><code class="inline-code">text_long</code></td><td>Multi-line free text input.</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="form-divider"></div>

                <h3 class="docs-subsection-title">Example request</h3>
                <div class="docs-code-block">
                    <span class="docs-code-label">curl</span>
<pre>curl -X POST https://example.com/api/surveys \
  -H "Content-Type: application/json" \
  -d '{
  "title": "Team Lunch Preferences",
  "expiration_days": 7,
  "questions": [
    {
      "label": "What cuisine do you prefer?",
      "type": "radio",
      "required": true,
      "choices": ["Italian", "Mexican", "Thai", "Other"]
    },
    {
      "label": "Any dietary restrictions?",
      "type": "text_short"
    }
  ]
}'</pre>
                </div>

                <div class="form-divider"></div>

                <h3 class="docs-subsection-title">Responses</h3>

                <p class="docs-label-inline"><span class="docs-status docs-status-success">201 Created</span> Survey created successfully.</p>
                <div class="docs-code-block" style="margin-bottom: 1.25rem;">
                    <span class="docs-code-label">JSON</span>
<pre>{
  "id": "a1b2c3d4e5",
  "url": "https://example.com/surveys?id=a1b2c3d4e5"
}</pre>
                </div>

                <p class="docs-label-inline"><span class="docs-status docs-status-error">400 Bad Request</span> Validation failed.</p>
                <div class="docs-code-block" style="margin-bottom: 1.25rem;">
                    <span class="docs-code-label">JSON</span>
<pre>{
  "errors": [
    "title is required.",
    "questions[0].choices must contain at least 2 non-empty options."
  ]
}</pre>
                </div>

                <p class="docs-label-inline"><span class="docs-status docs-status-error">400 Bad Request</span> Malformed JSON body.</p>
                <div class="docs-code-block" style="margin-bottom: 1.25rem;">
                    <span class="docs-code-label">JSON</span>
<pre>{ "error": "Invalid JSON body" }</pre>
                </div>

                <p class="docs-label-inline"><span class="docs-status docs-status-error">405 Method Not Allowed</span> Non-POST request.</p>
                <div class="docs-code-block">
                    <span class="docs-code-label">JSON</span>
<pre>{ "error": "Method not allowed" }</pre>
                </div>
            </div>

        </div><!-- /.docs-content -->
    </div><!-- /.docs-layout -->
</main>

<style>
.docs-hero {
    text-align: center;
    padding: 3.5rem 0 2.5rem;
}

.docs-layout {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 2rem;
    align-items: start;
    margin-bottom: 4rem;
}

.docs-nav {
    position: sticky;
    top: 1.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1rem;
    box-shadow: var(--shadow-sm);
}

.docs-nav-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--muted);
    margin-bottom: 0.6rem;
}

.docs-nav-link {
    display: block;
    font-size: 0.82rem;
    color: var(--text-warm);
    text-decoration: none;
    padding: 0.3rem 0.5rem;
    border-radius: var(--radius-sm);
    transition: background 0.15s, color 0.15s;
    font-family: monospace;
}
.docs-nav-link:hover {
    background: var(--accent-soft);
    color: var(--accent);
}

.docs-section {
    scroll-margin-top: 1.5rem;
}

.docs-section-title {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.35rem;
    margin-bottom: 0.85rem;
    color: var(--text);
}

.docs-subsection-title {
    font-family: var(--font-display);
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 0.65rem;
    color: var(--text);
}

.docs-endpoint-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
}

.docs-method {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.25rem 0.55rem;
    border-radius: var(--radius-sm);
    font-family: monospace;
}

.docs-method-post {
    background: #e8f5e9;
    color: #2e7d32;
}

.docs-path {
    font-family: monospace;
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--text);
}

.docs-prose {
    font-size: 0.925rem;
    color: var(--text-warm);
    line-height: 1.75;
    margin-bottom: 0.75rem;
}
.docs-prose:last-child { margin-bottom: 0; }

.docs-code-block {
    background: #1e1e2e;
    border-radius: var(--radius);
    padding: 1rem 1.25rem;
    position: relative;
    overflow-x: auto;
}
.docs-code-block pre,
.docs-code-block code:not(.inline-code) {
    color: #cdd6f4;
    font-family: 'Fira Code', 'Cascadia Code', 'Consolas', monospace;
    font-size: 0.82rem;
    line-height: 1.6;
    margin: 0;
    white-space: pre;
}
.docs-code-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #6c7086;
    margin-bottom: 0.5rem;
}

.inline-code {
    font-family: monospace;
    font-size: 0.85em;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 0.1em 0.35em;
    border-radius: 3px;
    color: var(--text);
}

.docs-table-wrap {
    overflow-x: auto;
}
.docs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}
.docs-table th {
    text-align: left;
    padding: 0.5rem 0.75rem;
    border-bottom: 1.5px solid var(--border);
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.docs-table td {
    padding: 0.6rem 0.75rem;
    border-bottom: 1px solid var(--border);
    color: var(--text-warm);
    line-height: 1.5;
    vertical-align: top;
}
.docs-table tr:last-child td { border-bottom: none; }

.docs-status {
    display: inline-block;
    font-size: 0.78rem;
    font-weight: 600;
    font-family: monospace;
    padding: 0.2rem 0.5rem;
    border-radius: var(--radius-sm);
    margin-right: 0.5rem;
}
.docs-status-success { background: #e8f5e9; color: #2e7d32; }
.docs-status-error   { background: #fdecea; color: #c62828; }

.docs-label-inline {
    font-size: 0.875rem;
    color: var(--text-warm);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem;
}

@media (max-width: 680px) {
    .docs-layout {
        grid-template-columns: 1fr;
    }
    .docs-nav {
        position: static;
    }
    .docs-hero { padding: 2rem 0 1.5rem; }
}
</style>

<?php include __DIR__ . '/components/footer.php'; ?>
