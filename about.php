<?php
require_once __DIR__ . '/includes/layout.php';
require_login();

// Project team. Put the photos in assets/img/ using the file names below (.jpg, .png or .webp),
// then replace the placeholder names and details.
$team = [
    ['photo' => 'student',    'name' => 'MAZARINA FITRIYAH BINTI IDRIS', 'role' => 'Student / Developer',
     'details' => ['Diploma in Computer Science', 'Faculty of Computer Science and Mathematics', 'Universiti Teknologi MARA (UiTM)']],
    ['photo' => 'supervisor', 'name' => 'Supervisor Name', 'role' => 'Supervisor',
     'details' => ['Position, Department', 'Organisation']],
];

function team_photo(string $base): ?string
{
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        $path = "assets/img/$base.$ext";
        if (is_file(__DIR__ . '/' . $path)) return $path . '?v=' . filemtime(__DIR__ . '/' . $path);
    }
    return null;
}

page_start('About', 'about'); ?>

<div class="page-header">
    <h1>About this project</h1>
    <p><?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?> &middot; Internship Individual Project, UiTM</p>
</div>

<section class="card">
    <h2>Project team</h2>
    <div class="team">
        <?php foreach ($team as $member): $photo = team_photo($member['photo']); ?>
            <figure class="team-member">
                <?php if ($photo): ?>
                    <img class="team-photo" src="<?= e($photo) ?>" alt="Photo of <?= e($member['name']) ?>" width="160" height="160">
                <?php else: ?>
                    <div class="team-photo team-photo-empty">
                        <span aria-hidden="true">&#128247;</span>
                        <small>Add photo<br><code><?= e($member['photo']) ?>.jpg</code></small>
                    </div>
                <?php endif; ?>
                <figcaption>
                    <span class="pill"><?= e($member['role']) ?></span>
                    <strong><?= e($member['name']) ?></strong>
                    <?php foreach ($member['details'] as $line): ?><span class="muted"><?= e($line) ?></span><?php endforeach; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>

<div class="grid-2">
    <section class="card prose">
        <h2>Problem statement</h2>
        <p>An Act changes many times over its life through amending laws and revisions. Each version is kept as a separate document, so finding out what a section said in a given year, or what changed between two versions, means opening several long documents and comparing them by hand. That is slow and easy to get wrong.</p>

        <h2>Objectives</h2>
        <ol>
            <li>To store every version of an Act in one central library backed by a database.</li>
            <li>To present the versions of an Act on a timeline so users can move between them easily.</li>
            <li>To show the text of each version in English and Malay, organised by Part, section and Schedule.</li>
            <li>To compare two versions automatically and highlight the sections and words that changed.</li>
            <li>To control access with user accounts and roles, and record activity for accountability.</li>
        </ol>

        <h2>Scope</h2>
        <ul>
            <li>Acts tagged in the legislative XML format (<code>ENG_LANG</code>, <code>MALAY_LANG</code>, <code>PART</code>, <code>SECTION</code>, <code>SCHEDULE</code>, <code>LISTOFAMENDMENTS</code>).</li>
            <li>Two user roles: administrator and viewer.</li>
            <li>Runs on a local web server (XAMPP) for internal use.</li>
        </ul>
    </section>

    <div>
        <section class="card prose">
            <h2>Main features</h2>
            <ul>
                <li>Act Library with search, upload, version details and history</li>
                <li>Interactive version timeline</li>
                <li>Bilingual reading view with contents and highlighted search</li>
                <li>Section-by-section comparison with word-level changes</li>
                <li>Print or save comparisons as PDF</li>
                <li>User accounts, roles, profiles and an activity log</li>
                <li>Light and dark mode, works on phones and tablets</li>
            </ul>
        </section>

        <section class="card prose">
            <h2>Technology</h2>
            <ul class="tech-list">
                <li>PHP 8</li>
                <li>MySQL / MariaDB</li>
                <li>PDO with prepared statements</li>
                <li>HTML5 &amp; CSS3</li>
                <li>JavaScript (DOMParser, Fetch API)</li>
                <li>XAMPP (Apache)</li>
            </ul>
            <h2>Security</h2>
            <ul>
                <li>Passwords stored as bcrypt hashes</li>
                <li>CSRF tokens on every form that changes data</li>
                <li>Uploaded XML is parsed without network access and sanitised before display</li>
                <li>Stored files are only served to signed-in users</li>
            </ul>
        </section>
    </div>
</div>

<?php page_end();
