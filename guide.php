<?php
require_once __DIR__ . '/includes/layout.php';
require_login();
page_start('User Guide', 'guide'); ?>

<div class="page-header">
    <h1>User Guide</h1>
    <p>How to store, read and compare versions of an Act.</p>
</div>

<div class="grid-2">
    <section class="card prose">
        <h2>Getting started</h2>
        <ol class="guide-steps">
            <li>
                <h3>Add an Act to the library</h3>
                <p>Administrators open <a href="library.php#upload">Act Library</a> and upload the XML files of an Act, one file per version. Files are grouped into Acts automatically using the <code>&lt;NUMBER&gt;</code> element.</p>
            </li>
            <li>
                <h3>Name the files so they sort correctly</h3>
                <p><code>MY_ACTS_1973_100_r.xml</code> is the original, <code>MY_ACTS_1973_100_r1980.xml</code> is the revision of 1980, and a name without <code>_r</code> is the current version.</p>
            </li>
            <li>
                <h3>Open the Act in the Viewer</h3>
                <p>Click <b>View</b> in the library. The versions appear on a timeline from oldest to newest, and the newest one opens first.</p>
            </li>
            <li>
                <h3>Read a version</h3>
                <p>Click a point on the timeline. Switch between <b>English</b> and <b>Malay</b>, use <b>Contents</b> to jump to a Part or section, and search to highlight matching words.</p>
            </li>
            <li>
                <h3>Compare two versions</h3>
                <p>Click <b>Compare versions</b>, choose the older and newer version, and filter to see sections that were added, removed or modified. Changed words are highlighted.</p>
            </li>
        </ol>
    </section>

    <section class="card prose">
        <h2>Reading the timeline</h2>
        <ul>
            <li>The <b>top label</b> shows the Act number and the year of that version.</li>
            <li>The <b>bottom label</b> shows the last amending law from the List of Amendments. If a file has no List of Amendments, it counts the laws cited in notes such as <code>[Am. by Act A1384]</code>.</li>
            <li>Use the <b>&larr; &rarr;</b> arrow keys to move between versions.</li>
        </ul>

        <h2>Reading a comparison</h2>
        <ul>
            <li><span class="badge added">added</span> a section that only exists in the newer version.</li>
            <li><span class="badge removed">removed</span> a section that was taken out.</li>
            <li><span class="badge modified">modified</span> the same section number with different wording: <ins>inserted</ins> and <del>deleted</del> words are highlighted.</li>
            <li>Use <b>Print / Save as PDF</b> to keep a copy of the comparison.</li>
        </ul>

        <h2>Roles</h2>
        <ul>
            <li><b>Viewer</b>: reads and compares Acts in the library.</li>
            <li><b>Admin</b>: also uploads and deletes Acts and manages users.</li>
        </ul>
    </section>
</div>

<section class="card faq">
    <h2>Frequently asked questions</h2>
    <details>
        <summary>My file was rejected as "not valid XML". What now?</summary>
        <p>The message shows the line and the problem the parser found. A common cause is an HTML entity such as <code>&amp;nbsp;</code> that XML does not define. Fix the file and upload it again.</p>
    </details>
    <details>
        <summary>How do I replace a version?</summary>
        <p>Upload a file with the same name to the same Act. The old file is replaced and the change is recorded in the Act's history.</p>
    </details>
    <details>
        <summary>Can I look at files without saving them?</summary>
        <p>Yes. In the Viewer, choose <b>Open files from your computer</b>. Those files stay in your browser and are not added to the library.</p>
    </details>
    <details>
        <summary>Why does a section show as removed and another as added?</summary>
        <p>Sections are matched by their number. If a section was renumbered, the old number shows as removed and the new number as added.</p>
    </details>
    <details>
        <summary>I forgot my password.</summary>
        <p>Ask an administrator to reset it on the Users page, then change it in <a href="profile.php">My profile</a>.</p>
    </details>
</section>

<?php page_end();
