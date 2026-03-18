<?php

/**
 * Default style guide for Lighthouse
 */
lh_set_data([
    'title' => 'Style Guide',
]);
?>
<section>
    <h1>Style Guide</h1>
    <p>
        This page demonstrates how raw semantic HTML looks with the default stylesheet.
        There are no utility classes or component classes in this document.
    </p>
</section>

<section>
    <h2>Layout</h2>
    <article>
        <h3>Article One</h3>
        <p>Semantic sections and articles create hierarchy without needing presentational wrappers.</p>
        <p><small>Updated daily with lightweight defaults.</small></p>
    </article>
    <article>
        <h3>Article Two</h3>
        <p>Content blocks stack naturally on small screens and breathe more on larger ones.</p>
        <p><small>Readable, responsive, and intentionally restrained.</small></p>
    </article>
    <article>
        <h3>Article Three</h3>
        <p>Each block exists to test spacing, rhythm, and contrast using only semantic HTML.</p>
        <p><small>No custom hooks required.</small></p>
    </article>
</section>

<section>
    <h2>Progress</h2>
    <p>The native <code>progress</code> element should inherit the same calm defaults as the rest of the page.</p>
    <progress value="60" max="100"></progress>
</section>

<section>
    <h2>Table</h2>
    <table>
        <caption>Feature overview</caption>
        <thead>
            <tr>
                <th scope="col">Feature</th>
                <th scope="col">Purpose</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">Semantic defaults</th>
                <td>Give plain HTML a polished appearance.</td>
            </tr>
            <tr>
                <th scope="row">Responsive media</th>
                <td>Keep images, video, and tables flexible.</td>
            </tr>
            <tr>
                <th scope="row">Native controls</th>
                <td>Prefer built-in HTML elements over custom components.</td>
            </tr>
        </tbody>
    </table>
</section>

<section>
    <h2>Form</h2>
    <form>
        <fieldset>
            <legend>Contact Example</legend>

            <p>
                <label for="name">Name</label>
                <input id="name" name="name" type="text" placeholder="Jane Doe" autocomplete="name" required minlength="2">
            </p>

            <p>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" placeholder="jane@example.com" autocomplete="email" required>
            </p>

            <p>
                <label for="topic">Topic</label>
                <select id="topic" name="topic" required>
                    <option value="">Choose a topic</option>
                    <option>General question</option>
                    <option>Documentation</option>
                    <option>Performance</option>
                </select>
            </p>

            <p>
                <label for="message">Message</label>
                <textarea id="message" name="message" placeholder="Write a short message" required minlength="20"></textarea>
            </p>

            <p>
                <label><input type="checkbox" name="updates"> Send occasional product updates</label>
            </p>

            <p>
                <button type="submit">Send message</button>
            </p>
        </fieldset>
    </form>
</section>

<section>
    <h2>Buttons</h2>
    <nav aria-label="Example actions">
        <ul>
            <li><button type="button">Preview</button></li>
            <li><button type="button">Publish</button></li>
            <li><button type="button">Archive</button></li>
        </ul>
    </nav>
</section>

<section>
    <h2>Disclosure</h2>
    <details name="style-guide-disclosure" open>
        <summary>What does classless mean here?</summary>
        <p>
            It means the stylesheet styles native HTML elements directly, so new pages begin with
            semantic markup instead of utility naming.
        </p>
    </details>
    <details name="style-guide-disclosure">
        <summary>When should markup change?</summary>
        <p>Whenever the HTML structure is fighting the semantics or readability of the document.</p>
    </details>
</section>

<section>
    <h2>Code & Blockquote</h2>
    <pre><code>&lt;div&gt;Example code&lt;/div&gt;</code></pre>
    <blockquote>
        Semantic HTML should be the source of both structure and meaning; CSS should refine it,
        not rescue it.
        <cite>Lighthouse design direction</cite>
    </blockquote>
</section>

<section>
    <h2>default.js</h2>
    <p>
        The JavaScript layer is attribute-driven, so HTML remains the interface for requests,
        transitions, server-sent events, and WebSockets.
    </p>
    <pre><code>&lt;button
    data-get="/partial/example"
    data-target="main"
    data-swap="innerHTML"
    data-transition="fade"&gt;
    Load content
&lt;/button&gt;</code></pre>
    <pre><code>&lt;form
    data-ajax
    action="/contact"
    method="post"
    data-target="main"
    data-transition="slide"&gt;
    ...
&lt;/form&gt;</code></pre>
    <pre><code>&lt;section
    data-sse="/events"
    data-sse-event="message"
    data-target="this"
    data-swap="append"&gt;
&lt;/section&gt;</code></pre>
    <pre><code>&lt;section data-ws="wss://example.com/socket" data-target="this"&gt;
    &lt;form data-ws-send="closest" data-ws-format="json"&gt;
        ...
    &lt;/form&gt;
&lt;/section&gt;</code></pre>
</section>

<section>
    <h2>Lists & Definitions</h2>
    <ul>
        <li>Use meaningful elements first.</li>
        <li>Prefer native browser behavior where possible.</li>
        <li>Keep the styling surface small and predictable.</li>
    </ul>
</section>
<section>
    <dl>
        <dt>Semantic markup</dt>
        <dd>HTML chosen for meaning and document structure.</dd>
        <dt>Classless CSS</dt>
        <dd>Element-driven styling with minimal custom hooks.</dd>
    </dl>
</section>