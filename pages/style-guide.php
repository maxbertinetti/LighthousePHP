<?php
// /pages/style-guide.php
// Style Guide page for Lighthouse
// Example content: layout, components, forms, buttons, utilities, advanced
lh_set_data([
    'title' => 'Style Guide',
]);
?>
<section class="container section">
    <h1>Style Guide</h1>
    <p>This page showcases all components and utilities from default.css, inspired by PicoCSS.</p>

    <h2>Layout</h2>
    <div class="grid">
        <div class="card">
            <div class="card-header">Card One</div>
            <div>First card content. The grid arranges cards automatically.</div>
            <div class="card-footer">Footer</div>
        </div>
        <div class="card">
            <div class="card-header">Card Two</div>
            <div>Second card content. On desktop these sit side by side.</div>
            <div class="card-footer">Footer</div>
        </div>
        <div class="card">
            <div class="card-header">Card Three</div>
            <div>Third card content. On mobile they stack vertically.</div>
            <div class="card-footer">Footer</div>
        </div>
    </div>

    <div class="alert info">Alert info</div>
    <div class="alert success">Alert success</div>
    <div class="alert warning">Alert warning</div>
    <div class="alert danger">Alert danger</div>

    <h2>Badge & Progress</h2>
    <span class="badge">Badge</span>
    <progress value="60" max="100"></progress>

    <h2>Table</h2>
    <table>
        <thead>
            <tr>
                <th>Column 1</th>
                <th>Column 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Value 1</td>
                <td>Value 2</td>
            </tr>
        </tbody>
    </table>

    <h2>Form</h2>
    <form>
        <div class="form-group">
            <label for="input1">Input</label>
            <input id="input1" type="text" placeholder="Text...">
        </div>
        <div class="form-group">
            <label for="select1">Select</label>
            <select id="select1">
                <option>Option 1</option>
                <option>Option 2</option>
            </select>
        </div>
        <div class="form-group">
            <label><input type="checkbox"> Checkbox</label>
            <label><input type="radio" name="r"> Radio</label>
        </div>
        <div class="form-actions">
            <button type="submit">Submit</button>
            <button type="button" class="secondary">Secondary</button>
        </div>
    </form>

    <h2>Buttons & Group</h2>
    <div role="group">
        <button>Left</button>
        <button>Middle</button>
        <button>Right</button>
    </div>

    <h2>Dropdown</h2>
    <div class="dropdown">
        <button class="dropdown-toggle">Dropdown</button>
        <div class="dropdown-menu">
            <a href="#">Action 1</a>
            <a href="#">Action 2</a>
        </div>
    </div>

    <h2>Accordion</h2>
    <div class="accordion">
        <div class="accordion-item open">
            <div class="accordion-header" tabindex="0">Section 1</div>
            <div class="accordion-content">Content 1</div>
        </div>
        <div class="accordion-item">
            <div class="accordion-header" tabindex="0">Section 2</div>
            <div class="accordion-content">Content 2</div>
        </div>
    </div>

    <h2>Code & Blockquote</h2>
    <pre><code>&lt;div&gt;Example code&lt;/div&gt;</code></pre>
    <blockquote>Example quotation</blockquote>

    <h2>Utility</h2>
    <div class="text-center">text-center</div>
    <div class="text-right">text-right</div>
    <div class="mt">mt (margin-top)</div>
    <div class="mb">mb (margin-bottom)</div>
    <div class="p">p (padding)</div>

    <h2>Conditional Styling</h2>
    <div class="nodefault">
        <button>This button has NO default.css styling</button>
    </div>
</section>