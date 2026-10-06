const fileInput = document.getElementById('fileInput');
const uploadArea = document.getElementById('uploadArea');
const actViewport = document.getElementById('actViewport');
const timelineTrack = document.getElementById('timelineTrack');
const timelineBox = document.getElementById('timelineBox');
const notice = document.getElementById('notice');

let actDataStore = {};

// Language and search carry over when switching between versions.
const viewState = { lang: 'ENG', query: '' };

// Set by viewer.php. libraryActId is the library Act on screen, or null for local or sample files.
const LV = window.LV || {};
let libraryActId = null;

// ---------- Local files (click, keyboard, drag and drop) ----------
function openLocalFiles(files) {
    libraryActId = null;
    handleFiles(files);
}
uploadArea.addEventListener('click', () => fileInput.click());
['dragenter', 'dragover'].forEach(ev => uploadArea.addEventListener(ev, e => { e.preventDefault(); uploadArea.classList.add('dragover'); }));
['dragleave', 'drop'].forEach(ev => uploadArea.addEventListener(ev, e => { e.preventDefault(); uploadArea.classList.remove('dragover'); }));
uploadArea.addEventListener('drop', e => openLocalFiles(Array.from(e.dataTransfer.files)));
fileInput.addEventListener('change', e => openLocalFiles(Array.from(e.target.files)));

// ---------- Sample data (fictional Act, three versions) ----------
document.getElementById('sampleBtn').addEventListener('click', () => openLocalFiles(makeSampleFiles()));

// ---------- Library (versions stored in the database) ----------
async function openFromLibrary(actId, startCompare) {
    document.getElementById('viewerEmpty')?.remove();
    timelineBox.style.display = 'none';
    actViewport.style.display = 'block';
    actViewport.innerHTML = '<div class="empty-state">Loading versions from the library...</div>';
    try {
        const res = await fetch(`api/versions.php?act=${encodeURIComponent(actId)}`, { credentials: 'same-origin' });
        if (!res.ok) throw new Error(`the server answered ${res.status}`);
        const { versions } = await res.json();
        if (!versions.length) throw new Error('this Act has no versions');
        const files = [];
        for (let i = 0; i < versions.length; i++) {
            actViewport.firstElementChild.textContent = `Downloading version ${i + 1} of ${versions.length}: ${versions[i].file_name}`;
            const fileRes = await fetch(`api/file.php?id=${versions[i].id}`, { credentials: 'same-origin' });
            if (!fileRes.ok) throw new Error(`${versions[i].file_name} could not be loaded`);
            files.push(new File([await fileRes.blob()], versions[i].file_name, { type: 'text/xml' }));
        }
        libraryActId = actId;
        await handleFiles(files);
        if (startCompare && Object.keys(actDataStore).length > 1) openCompare();
    } catch (err) {
        actViewport.innerHTML = '';
        showNotice('Could not open this Act: ' + err.message + '.');
    }
}

function logActivity(action, details) {
    if (!LV.csrf) return;
    const body = new URLSearchParams({ action, details, act_id: libraryActId || '', csrf: LV.csrf });
    fetch('api/log.php', { method: 'POST', body, credentials: 'same-origin' }).catch(() => {});
}

function makeSampleFiles() {
    const S = (sno, en, enText, bm, bmText) => ({ sno, en, enText, bm, bmText });
    const s1 = S('1.', 'Short title', 'This Act may be cited as the Sample Act.', 'Tajuk ringkas', 'Akta ini bolehlah dinamakan Akta Sampel.');
    const s2 = S('2.', 'Interpretation', 'In this Act, "licence" means a licence issued under section 4.', 'Tafsiran', 'Dalam Akta ini, "lesen" ertinya lesen yang dikeluarkan di bawah seksyen 4.');
    const s2b = S('2.', 'Interpretation', 'In this Act, "licence" means a licence issued under section 4, and "premises" includes any land or building.', 'Tafsiran', 'Dalam Akta ini, "lesen" ertinya lesen yang dikeluarkan di bawah seksyen 4, dan "premis" termasuk apa-apa tanah atau bangunan.');
    const s3 = S('3.', 'Application', 'This Act applies to all premises in Malaysia.', 'Pemakaian', 'Akta ini terpakai bagi semua premis di Malaysia.');
    const s4 = S('4.', 'Licence required', 'No person shall operate a business without a licence granted by the Minister.', 'Lesen dikehendaki', 'Tiada seorang pun boleh menjalankan perniagaan tanpa lesen yang diberikan oleh Menteri.');
    const s4b = S('4.', 'Licence required', 'No person shall operate a business without a licence granted by the Director General.', 'Lesen dikehendaki', 'Tiada seorang pun boleh menjalankan perniagaan tanpa lesen yang diberikan oleh Ketua Pengarah.');
    const s4a = S('4A.', 'Register of licences', 'The Director General shall keep a register of all licences granted.', 'Daftar lesen', 'Ketua Pengarah hendaklah menyimpan daftar semua lesen yang diberikan.');
    const s4a2 = S('4A.', 'Register of licences', 'The Director General shall keep and publish a register of all licences granted.', 'Daftar lesen', 'Ketua Pengarah hendaklah menyimpan dan menerbitkan daftar semua lesen yang diberikan.');
    const s5 = S('5.', 'Penalty', 'Any person who contravenes section 4 commits an offence and shall, on conviction, be liable to a fine not exceeding one thousand ringgit.', 'Penalti', 'Mana-mana orang yang melanggar seksyen 4 melakukan suatu kesalahan dan boleh, apabila disabitkan, didenda tidak melebihi satu ribu ringgit.');
    const s5b = S('5.', 'Penalty', 'Any person who contravenes section 4 commits an offence and shall, on conviction, be liable to a fine not exceeding five thousand ringgit.', 'Penalti', 'Mana-mana orang yang melanggar seksyen 4 melakukan suatu kesalahan dan boleh, apabila disabitkan, didenda tidak melebihi lima ribu ringgit.');
    const s6 = S('6.', 'Electronic submission', 'An application under this Act may be made by electronic means.', 'Penyerahan elektronik', 'Permohonan di bawah Akta ini boleh dibuat secara elektronik.');

    const esc = s => s.replace(/&/g, '&amp;').replace(/</g, '&lt;');
    const build = (title, amends, secs, schedule) => {
        const rows = amends.map(a => `<tr><td>${esc(a[0])}</td><td>${esc(a[1])}</td></tr>`).join('');
        const lang = (tag, k) => `<${tag}><PART><PARTNUMBER>${k === 'en' ? 'PART I' : 'BAHAGIAN I'}</PARTNUMBER><PARTTITLE>${k === 'en' ? 'GENERAL' : 'AM'}</PARTTITLE>` +
            secs.map(s => `<SECTION><SNO>${s.sno}</SNO><ST>${esc(s[k])}</ST><p>${esc(s[k + 'Text'])}</p></SECTION>`).join('') + '</PART>' +
            (schedule ? `<SCHEDULE><SCHEDULENO>${k === 'en' ? 'FIRST SCHEDULE' : 'JADUAL PERTAMA'}</SCHEDULENO><SCHEDULETITLE>${k === 'en' ? 'Fees' : 'Fi'}</SCHEDULETITLE><p>${k === 'en' ? 'Application fee: RM100.' : 'Fi permohonan: RM100.'}</p></SCHEDULE>` : '') +
            `</${tag}>`;
        return `<ACT><NUMBER>Act 999</NUMBER><TITLE>${title}</TITLE><LISTOFAMENDMENTS><table><tr><th>Amending law</th><th>In force from</th></tr>${rows}</table></LISTOFAMENDMENTS>${lang('ENG_LANG', 'en')}${lang('MALAY_LANG', 'bm')}</ACT>`;
    };
    const a1 = ['Sample (Amendment) Act 2012', '1 January 2012'];
    const a2 = ['Sample (Amendment) Act 2015', '1 March 2015'];
    const a3 = ['Sample (Amendment) Act 2024', '1 June 2024'];
    const file = (name, xml) => new File([xml], name, { type: 'text/xml' });
    return [
        file('Sample_Act_R.xml', build('SAMPLE ACT (ORIGINAL 1990)', [], [s1, s2, s3, s4, s5], false)),
        file('Sample_Act_R2015.xml', build('SAMPLE ACT (R2015)', [a1, a2], [s1, s2b, s4b, s4a, s5], false)),
        file('Sample_Act.xml', build('SAMPLE ACT (CURRENT 2026)', [a1, a2, a3], [s1, s2b, s4b, s4a2, s5b, s6], true))
    ];
}

function showNotice(msg) {
    notice.textContent = msg;
    notice.style.display = msg ? 'block' : 'none';
}

function getYear(fileName, titleText = '') {
    const r = fileName.match(/R(\d{4})/i) || titleText.match(/R(\d{4})/i);
    if (r) return r[1];
    const y = titleText.match(/\d{4}/);
    return y ? y[0] : null;
}

function sortScore(name) {
    const n = name.toLowerCase();
    if (n.endsWith('_r.xml')) return 1;
    if (n.includes('_r') && /\d{4}/.test(n)) return 2;
    return 3;
}

async function handleFiles(files) {
    files = files.filter(f => f.name.toLowerCase().endsWith('.xml'));
    if (files.length === 0) { showNotice('No XML files selected.'); return; }
    showNotice('');
    document.getElementById('viewerEmpty')?.remove();

    // Original first, then revisions by year, then current.
    files.sort((a, b) => sortScore(a.name) - sortScore(b.name) ||
        (getYear(a.name) || '').localeCompare(getYear(b.name) || '') ||
        a.name.localeCompare(b.name));

    actDataStore = {};
    timelineTrack.innerHTML = '';
    timelineBox.style.display = 'block';
    actViewport.style.display = 'block';
    actViewport.innerHTML = '<div class="empty-state">Loading...</div>';
    const status = actViewport.firstElementChild;

    const failed = [];
    let firstDot = null;
    const parser = new DOMParser();
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        status.textContent = `Reading file ${i + 1} of ${files.length}: ${file.name}`;
        await new Promise(r => setTimeout(r)); // let the message paint before parsing a large file
        const xmlDoc = parser.parseFromString(await file.text(), 'text/xml');
        if (xmlDoc.getElementsByTagName('parsererror').length) { failed.push(file.name); continue; }
        const actID = `id_${i}`;
        actDataStore[actID] = { doc: xmlDoc, name: file.name };
        const dot = renderTimelineNode(xmlDoc, actID, file.name);
        if (!firstDot) firstDot = dot;
    }

    const messages = [];
    if (failed.length) messages.push('Could not read (invalid XML): ' + failed.join(', ') + '.');
    const actNumbers = new Set(Object.values(actDataStore)
        .map(v => (v.doc.querySelector('NUMBER')?.textContent || '').replace(/\s+/g, ' ').trim().toUpperCase())
        .filter(Boolean));
    if (actNumbers.size > 1) messages.push(`These files look like different Acts (${[...actNumbers].join(', ')}). Upload versions of one Act only for an accurate timeline and comparison.`);
    showNotice(messages.join(' '));

    if (!firstDot) {
        timelineBox.style.display = 'none';
        actViewport.innerHTML = '<div class="empty-state">No valid XML file to display.</div>';
        return;
    }
    renderSummary();
    // Open the latest version by default so the page is never empty.
    const dots = timelineTrack.querySelectorAll('.dot');
    const last = dots[dots.length - 1];
    switchToAct(last.dataset.actId, last);
}

function renderSummary() {
    const versions = Object.values(actDataStore);
    const summary = document.getElementById('actSummary');
    const latest = versions[versions.length - 1];
    const clean = s => (s || '').replace(/\s+/g, ' ').trim();
    const title = clean(latest.doc.querySelector('TITLE')?.textContent) || latest.name;
    const number = clean(latest.doc.querySelector('NUMBER')?.textContent);
    const years = versions.map(v => v.year).filter(y => /^\d{4}$/.test(y)).sort();
    const range = years.length ? (years[0] === years[years.length - 1] ? years[0] : `${years[0]} to ${years[years.length - 1]}`) : 'Unknown';
    const langs = [latest.doc.querySelector('ENG_LANG') && 'English', latest.doc.querySelector('MALAY_LANG') && 'Malay'].filter(Boolean).join(', ') || 'None found';
    const sections = latest.doc.querySelectorAll('SECTION, SEKSYEN').length;
    summary.innerHTML = `<div class="act-summary-head">
            <h2>${escapeHtml(title)}</h2>
            ${libraryActId
                ? `<a class="tool-btn" href="act.php?id=${encodeURIComponent(libraryActId)}">Act details &amp; history</a>`
                : '<span class="pill">Opened from your computer, not saved</span>'}
        </div>
        <div class="meta">
            ${number ? `<span>Act number: <b>${escapeHtml(number)}</b></span>` : ''}
            <span>Versions loaded: <b>${versions.length}</b></span>
            <span>Years covered: <b>${escapeHtml(range)}</b></span>
            <span>Languages: <b>${escapeHtml(langs)}</b></span>
            <span>Sections in latest: <b>${sections}</b></span>
        </div>`;
    summary.style.display = 'block';
}

function renderTimelineNode(xml, actID, fileName) {
    const actNo = xml.querySelector('NUMBER')?.textContent || 'ACT';
    const titleText = xml.querySelector('TITLE')?.textContent || '';
    const year = getYear(fileName, titleText);
    const kind = ['Original', 'Revised', 'Current'][sortScore(fileName) - 1];
    const displayYear = year || kind;

    let lastAmend = '';
    const amendTable = xml.querySelector('LISTOFAMENDMENTS table');
    if (amendTable) {
        const rows = Array.from(amendTable.querySelectorAll('tr'));
        if (rows.length > 1) {
            const cells = rows[rows.length - 1].querySelectorAll('td');
            if (cells.length > 0) lastAmend = cells[0].textContent.trim();
        }
    }

    // Without a List of Amendments table, fall back to the [Am. by ...] notes in the text.
    const fromNotes = !amendTable;
    const amendments = fromNotes ? parseAmendNotes(xml) : parseAmendments(xml);
    Object.assign(actDataStore[actID], {
        year: displayYear,
        label: `${displayYear} (${fileName})`,
        amendments,
        amendSource: fromNotes ? 'notes' : 'table'
    });

    let amendLine;
    if (lastAmend) {
        amendLine = `as amended by <span class="blue-bold">${escapeHtml(lastAmend)}</span>`;
    } else if (!fromNotes) {
        amendLine = '<span class="blue-bold">No amendments listed</span>';
    } else if (!amendments.length) {
        amendLine = '<span class="blue-bold">No amendment notes found</span>';
    } else {
        const loaded = Object.values(actDataStore);
        const prev = loaded[loaded.length - 2];
        const prevKeys = new Set((prev?.amendments || []).map(r => lawKey(r[0])));
        const added = prev ? amendments.filter(r => !prevKeys.has(lawKey(r[0]))).length : 0;
        amendLine = `notes cite <span class="blue-bold">${amendments.length} amending law${amendments.length === 1 ? '' : 's'}</span>` +
            (added ? ` <span class="yr">(+${added} new)</span>` : '');
    }

    const node = document.createElement('div');
    node.className = 'node';
    node.setAttribute('role', 'listitem');
    node.innerHTML = `
        <div class="connector"></div>
        <div class="label-top">${escapeHtml(actNo)} <span class="yr">(${escapeHtml(displayYear)})</span></div>
        <button type="button" class="dot" data-act-id="${actID}" aria-label="${escapeHtml(actNo)} version ${escapeHtml(displayYear)}"></button>
        <div class="label-bottom">${year ? `${escapeHtml(kind)} version, ${escapeHtml(year)}` : `${escapeHtml(kind)} version`}<br>${amendLine}</div>
    `;
    const dot = node.querySelector('.dot');
    dot.addEventListener('click', () => switchToAct(actID, dot));
    dot.addEventListener('keydown', e => {
        if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
        e.preventDefault();
        const dots = Array.from(timelineTrack.querySelectorAll('.dot'));
        const next = dots[dots.indexOf(dot) + (e.key === 'ArrowRight' ? 1 : -1)];
        if (next) { next.focus(); next.click(); }
    });
    timelineTrack.appendChild(node);
    return dot;
}

// ---------- Structure rendering (logic unchanged from v3) ----------
const STRUCTURE_SELECTOR = 'PART, SUBPART, SUBSUBPART, SCHEDULE, SECTION, SEKSYEN, LISTOFAMENDMENTS';
const STRUCTURE_TAGS = new Set(STRUCTURE_SELECTOR.split(', '));
const GROUP_HEADINGS = {
    PART:       { number: 'PARTNUMBER',   title: 'PARTTITLE',    style: 'b', level: 2 },
    SUBPART:    { number: 'SUBPARTNO',    title: 'SUBPARTTITLE', style: 'i', level: 3 },
    SUBSUBPART: { number: 'SUBSUBPARTNO', title: 'SUBSUBPARTTITLE', style: 'i', level: 4 },
    SCHEDULE:   { number: 'SCHEDULENO',   title: 'SCHEDULETITLE', style: 'b', level: 2 }
};
const xmlSerializer = new XMLSerializer();

function escapeHtml(text) {
    const span = document.createElement('span');
    span.textContent = text;
    return span.innerHTML;
}

function ownHeading(node, tagName) {
    return Array.from(node.querySelectorAll(tagName)).find(
        heading => heading.closest(STRUCTURE_SELECTOR) === node
    ) || null;
}

function withoutOwnHeadings(node, tagNames) {
    const clone = node.cloneNode(true);
    const formattingTags = new Set(['p', 'b', 'i', 'strong', 'em', 'span']);
    tagNames.forEach(tagName => {
        Array.from(clone.querySelectorAll(tagName)).forEach(heading => {
            if (heading.closest(STRUCTURE_SELECTOR) !== clone) return;
            let parent = heading.parentElement;
            heading.remove();
            while (parent && parent !== clone &&
                   formattingTags.has(parent.tagName.toLowerCase()) &&
                   !parent.textContent.trim() && parent.children.length === 0) {
                const next = parent.parentElement;
                parent.remove();
                parent = next;
            }
        });
    });
    return clone;
}

// Strip active content from uploaded XML before it enters the page.
function sanitize(fragment) {
    fragment.querySelectorAll('script, iframe, object, embed, style, link, meta').forEach(el => el.remove());
    fragment.querySelectorAll('*').forEach(el => {
        Array.from(el.attributes).forEach(attr => {
            const name = attr.name.toLowerCase();
            const val = attr.value.trim().toLowerCase();
            if (name.startsWith('on') || ((name === 'href' || name === 'src') && val.startsWith('javascript:'))) {
                el.removeAttribute(attr.name);
            }
        });
    });
}

function appendMarkup(xmlNode, target) {
    const template = document.createElement('template');
    template.innerHTML = xmlSerializer.serializeToString(xmlNode);
    sanitize(template.content);
    target.appendChild(template.content);
}

function renderChildren(xmlParent, target, includeContent = false) {
    Array.from(xmlParent.childNodes).forEach(child => {
        if (child.nodeType === Node.ELEMENT_NODE) {
            const tag = child.tagName.toUpperCase();
            if (STRUCTURE_TAGS.has(tag)) {
                renderStructure(child, target);
            } else if (child.querySelector(STRUCTURE_SELECTOR)) {
                if (includeContent) {
                    const shell = document.createElement(child.tagName.toLowerCase());
                    Array.from(child.attributes).forEach(attr => {
                        if (!attr.name.toLowerCase().startsWith('on')) shell.setAttribute(attr.name, attr.value);
                    });
                    target.appendChild(shell);
                    renderChildren(child, shell, true);
                } else {
                    renderChildren(child, target, false);
                }
            } else if (includeContent) {
                appendMarkup(child, target);
            }
        } else if (includeContent &&
                   (child.nodeType === Node.TEXT_NODE || child.nodeType === Node.CDATA_SECTION_NODE)) {
            target.appendChild(document.createTextNode(child.textContent));
        }
    });
}

function renderStructure(node, target) {
    const tag = node.tagName.toUpperCase();

    if (tag === 'LISTOFAMENDMENTS') {
        const am = document.createElement('div');
        am.className = 'amend-container';
        am.dataset.xmlTag = tag;
        const heading = document.createElement('h3');
        heading.style.cssText = 'font-size:14px; margin:0 0 10px; color:var(--accent);';
        heading.textContent = 'LIST OF AMENDMENTS';
        am.appendChild(heading);
        renderChildren(node, am, true);
        target.appendChild(am);
        return;
    }

    if (tag === 'SECTION' || tag === 'SEKSYEN') {
        renderSection(node, target);
        return;
    }

    const config = GROUP_HEADINGS[tag];
    if (!config) return;
    const group = document.createElement('div');
    group.className = `structure-group ${tag.toLowerCase()}-group`;
    group.dataset.xmlTag = tag;
    const heading = document.createElement('div');
    heading.className = `structure-heading ${tag.toLowerCase()}-container`;
    heading.setAttribute('role', 'heading');
    heading.setAttribute('aria-level', String(config.level));

    [config.number, config.title].forEach(tagName => {
        const text = ownHeading(node, tagName)?.textContent.trim() || '';
        if (!text) return;
        const line = document.createElement('p');
        const formatted = document.createElement(config.style);
        formatted.textContent = text;
        line.appendChild(formatted);
        heading.appendChild(line);
    });
    if (heading.children.length) {
        if (tag === 'SCHEDULE') {
            heading.classList.add('schedule-header');
            heading.setAttribute('role', 'button');
            heading.setAttribute('tabindex', '0');
            heading.setAttribute('aria-expanded', 'false');
            const hint = document.createElement('div');
            hint.className = 'schedule-toggle';
            hint.textContent = 'Click to open Schedule';
            heading.appendChild(hint);
        }
        group.appendChild(heading);
    }

    const body = document.createElement('div');
    body.className = 'structure-content' + (tag === 'SCHEDULE' ? ' schedule-content' : '');
    const clone = withoutOwnHeadings(node, [config.number, config.title]);
    renderChildren(clone, body, true);
    group.appendChild(body);

    if (tag === 'SCHEDULE') {
        const toggleSchedule = () => {
            const expanded = heading.getAttribute('aria-expanded') !== 'true';
            body.style.display = expanded ? 'block' : 'none';
            heading.setAttribute('aria-expanded', String(expanded));
            const hint = heading.querySelector('.schedule-toggle');
            if (hint) hint.textContent = expanded ? 'Click to close Schedule' : 'Click to open Schedule';
        };
        heading.onclick = toggleSchedule;
        heading.onkeydown = (event) => {
            if (!['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            toggleSchedule();
        };
    }

    target.appendChild(group);
}

function renderSection(node, target) {
    const sno = ownHeading(node, 'SNO')?.textContent.trim() || '';
    const st = ownHeading(node, 'ST')?.textContent.trim() || '';
    const clone = withoutOwnHeadings(node, ['SNO', 'ST']);
    const wrapper = document.createElement('div');
    wrapper.className = 'section-wrapper';
    wrapper.dataset.xmlTag = node.tagName.toUpperCase();
    wrapper.innerHTML = `
        <div class="section-header" role="button" tabindex="0" aria-expanded="false">
            <span class="chevron" aria-hidden="true">&#9654;</span>
            <div class="section-title">${escapeHtml(sno)} ${escapeHtml(st)}</div>
        </div>
        <div class="section-content"></div>
    `;
    const header = wrapper.querySelector('.section-header');
    const body = wrapper.querySelector('.section-content');
    renderChildren(clone, body, true);
    const toggle = () => setSection(wrapper, header.getAttribute('aria-expanded') !== 'true');
    header.onclick = toggle;
    header.onkeydown = (event) => {
        if (!['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        toggle();
    };
    target.appendChild(wrapper);
}

function setSection(wrapper, expanded) {
    wrapper.querySelector('.section-content').style.display = expanded ? 'block' : 'none';
    wrapper.querySelector('.section-header').setAttribute('aria-expanded', String(expanded));
}

// ---------- Version comparison ----------
const compareBtn = document.getElementById('compareBtn');
compareBtn.addEventListener('click', openCompare);

function parseAmendments(doc) {
    // Every data row of the List of Amendments, as an array of cell texts.
    const table = doc.querySelector('LISTOFAMENDMENTS table');
    if (!table) return [];
    return Array.from(table.querySelectorAll('tr'))
        .map(tr => Array.from(tr.querySelectorAll('td')).map(td => td.textContent.replace(/\s+/g, ' ').trim()))
        .filter(cells => cells.length && cells.some(Boolean));
}

// Amending laws cited in notes such as [Am. by Act A1384], [Ins. by P.U. (A) 12/1999] or
// [Am. by L.N. 481/55; Act 17/66; Act A334.]. Returned as one-cell rows, in order of first mention.
const AMEND_WORD = /\b(Am|Amended|Ins|Inserted|Subs?|Substituted|Del|Deleted|Added|Rep|Repealed|Revoked|Pind|Mas|Ganti|Gantian|Dipotong|Ditinggalkan)\b/i;
const LAW_REF = /\b(?:Act|Akta)\s*A?\s?\d+(?:\/\d{2,4})?|\bP\.\s*U\.\s*\(\s*[AB]\s*\)\s*\d+\/\d{2,4}|\b(?:L\.\s*N|F\.\s*M)\.\s*\d+\/\d{2,4}/gi;
const lawKey = s => s.replace(/\s+/g, '').replace(/\.$/, '').toUpperCase().replace(/^AKTA/, 'ACT');

function parseAmendNotes(doc) {
    const laws = new Map();
    for (const note of doc.documentElement.textContent.matchAll(/\[([^\[\]]{1,250})\]/g)) {
        if (!AMEND_WORD.test(note[1])) continue;
        for (const ref of note[1].matchAll(LAW_REF)) {
            const key = lawKey(ref[0]);
            if (!laws.has(key)) laws.set(key, ref[0].replace(/\s+/g, ' ').replace(/^Akta/i, 'Act'));
        }
    }
    return Array.from(laws.values(), law => [law]);
}

function extractSections(doc, lang) {
    const root = doc.querySelector(lang === 'ENG' ? 'ENG_LANG' : 'MALAY_LANG');
    if (!root) return null;
    const list = [];
    const seen = {};
    root.querySelectorAll('SECTION, SEKSYEN, SCHEDULE').forEach(node => {
        const isSchedule = node.tagName.toUpperCase() === 'SCHEDULE';
        const [noTag, titleTag] = isSchedule ? ['SCHEDULENO', 'SCHEDULETITLE'] : ['SNO', 'ST'];
        const sno = (ownHeading(node, noTag)?.textContent || '').replace(/\s+/g, ' ').trim();
        const st = (ownHeading(node, titleTag)?.textContent || '').replace(/\s+/g, ' ').trim();
        const clone = withoutOwnHeadings(node, [noTag, titleTag]);
        clone.querySelectorAll('SECTION, SEKSYEN, SCHEDULE').forEach(n => n.remove()); // nested items are their own entries
        const text = clone.textContent.replace(/\s+/g, ' ').trim();
        if (isSchedule && !text) return; // a Schedule made only of sections is covered by those sections
        const base = (isSchedule ? 'SCH ' : '') + (sno.replace(/[.\s]+$/, '') || st);
        seen[base] = (seen[base] || 0) + 1;
        list.push({ key: seen[base] > 1 ? `${base}#${seen[base]}` : base, sno, st, text });
    });
    return list;
}

function diffWords(a, b) {
    // Word-level diff: trim shared prefix/suffix, then LCS on the middle.
    const A = a.split(' ').filter(Boolean), B = b.split(' ').filter(Boolean);
    let s = 0;
    while (s < A.length && s < B.length && A[s] === B[s]) s++;
    let e = 0;
    while (e < A.length - s && e < B.length - s && A[A.length - 1 - e] === B[B.length - 1 - e]) e++;
    const a2 = A.slice(s, A.length - e), b2 = B.slice(s, B.length - e);
    const ops = A.slice(0, s).map(w => ['=', w]);
    if (a2.length * b2.length > 4e6) {
        a2.forEach(w => ops.push(['-', w]));
        b2.forEach(w => ops.push(['+', w]));
    } else {
        const n = a2.length, m = b2.length, w = m + 1;
        const dp = new Uint32Array((n + 1) * w);
        for (let i = n - 1; i >= 0; i--) {
            for (let j = m - 1; j >= 0; j--) {
                dp[i * w + j] = a2[i] === b2[j] ? dp[(i + 1) * w + j + 1] + 1 : Math.max(dp[(i + 1) * w + j], dp[i * w + j + 1]);
            }
        }
        let i = 0, j = 0;
        while (i < n && j < m) {
            if (a2[i] === b2[j]) { ops.push(['=', a2[i]]); i++; j++; }
            else if (dp[(i + 1) * w + j] >= dp[i * w + j + 1]) ops.push(['-', a2[i++]]);
            else ops.push(['+', b2[j++]]);
        }
        while (i < n) ops.push(['-', a2[i++]]);
        while (j < m) ops.push(['+', b2[j++]]);
    }
    A.slice(A.length - e).forEach(x => ops.push(['=', x]));
    return ops;
}

function diffHtml(a, b) {
    const wrap = { '-': 'del', '+': 'ins' };
    const out = [];
    let run = null;
    diffWords(a, b).forEach(([type, word]) => {
        if (run && run.type === type) run.words.push(word);
        else { run = { type, words: [word] }; out.push(run); }
    });
    return out.map(r => {
        const t = escapeHtml(r.words.join(' '));
        return r.type === '=' ? t : `<${wrap[r.type]}>${t}</${wrap[r.type]}>`;
    }).join(' ');
}

function compareSections(fromList, toList) {
    const fromMap = new Map(fromList.map(s => [s.key, s]));
    const toMap = new Map(toList.map(s => [s.key, s]));
    // Removed sections are placed after the last surviving section that preceded them.
    const removedAfter = new Map();
    let anchor = '';
    fromList.forEach(f => {
        if (toMap.has(f.key)) { anchor = f.key; return; }
        if (!removedAfter.has(anchor)) removedAfter.set(anchor, []);
        removedAfter.get(anchor).push({ status: 'removed', from: f });
    });
    const results = [...(removedAfter.get('') || [])];
    toList.forEach(t => {
        const f = fromMap.get(t.key);
        if (!f) results.push({ status: 'added', to: t });
        else if (f.text === t.text && f.st === t.st) results.push({ status: 'same', from: f, to: t });
        else results.push({ status: 'modified', from: f, to: t });
        results.push(...(removedAfter.get(t.key) || []));
    });
    return results;
}

function cardParts(r) {
    const item = r.to || r.from;
    const title = `${item.sno} ${item.st}`.trim() || item.key;
    let titleHtml = escapeHtml(title), body;
    if (r.status === 'added') body = `<ins>${escapeHtml(r.to.text)}</ins>`;
    else if (r.status === 'removed') body = `<del>${escapeHtml(r.from.text)}</del>`;
    else {
        if (r.status === 'modified' && r.from.st !== r.to.st) titleHtml = `${escapeHtml(r.to.sno)} ${diffHtml(r.from.st, r.to.st)}`;
        body = r.status === 'modified' ? diffHtml(r.from.text, r.to.text) : escapeHtml(r.to.text);
    }
    return { titleHtml, body: body || '<em>(no text)</em>' };
}

function openCompare() {
    const ids = Object.keys(actDataStore);
    if (ids.length < 2) { showNotice('Upload at least two versions to compare.'); return; }
    showNotice('');
    document.querySelectorAll('.dot').forEach(d => {
        d.classList.remove('active');
        d.removeAttribute('aria-current');
        d.closest('.node').classList.remove('active');
    });
    compareBtn.classList.add('active');

    const options = ids.map(id => `<option value="${id}">${escapeHtml(actDataStore[id].label)}</option>`).join('');
    actViewport.innerHTML = `
        <div class="act-separator">Compare versions</div>
        <div class="toolbar compare-bar">
            <label>From (older) <select id="cmpFrom">${options}</select></label>
            <label>To (newer) <select id="cmpTo">${options}</select></label>
            <label>Language <select id="cmpLang"><option value="ENG">English</option><option value="BM">Malay</option></select></label>
            <button type="button" class="tool-btn swap-btn" id="cmpSwap" title="Swap From and To">&#8644; Swap</button>
            <button type="button" class="tool-btn" id="cmpPrint">&#128424; Print / Save as PDF</button>
        </div>
        <div id="cmpOut"></div>`;
    const fromSel = actViewport.querySelector('#cmpFrom');
    const toSel = actViewport.querySelector('#cmpTo');
    const langSel = actViewport.querySelector('#cmpLang');
    fromSel.value = ids[ids.length - 2];
    toSel.value = ids[ids.length - 1];
    langSel.value = viewState.lang;
    const rerender = () => {
        viewState.lang = langSel.value;
        renderCompare(fromSel.value, toSel.value, langSel.value);
        if (fromSel.value !== toSel.value) {
            const lang = langSel.value === 'ENG' ? 'English' : 'Malay';
            logActivity('compare', `${actDataStore[fromSel.value].year} to ${actDataStore[toSel.value].year} (${lang})`);
        }
    };
    [fromSel, toSel, langSel].forEach(s => s.addEventListener('change', rerender));
    actViewport.querySelector('#cmpSwap').addEventListener('click', () => {
        [fromSel.value, toSel.value] = [toSel.value, fromSel.value];
        rerender();
    });
    actViewport.querySelector('#cmpPrint').addEventListener('click', () => {
        const cards = actViewport.querySelectorAll('.cmp-card');
        const wasOpen = Array.from(cards, c => c.open);
        cards.forEach(c => { c.open = true; });
        window.print();
        cards.forEach((c, i) => { c.open = wasOpen[i]; });
    });
    rerender();
    actViewport.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderCompare(fromId, toId, lang, filter = 'changes') {
    const out = actViewport.querySelector('#cmpOut');
    const from = actDataStore[fromId], to = actDataStore[toId];
    if (fromId === toId) { out.innerHTML = '<div class="empty-state">Choose two different versions.</div>'; return; }
    const fromList = extractSections(from.doc, lang), toList = extractSections(to.doc, lang);
    if (!fromList || !toList) {
        out.innerHTML = `<div class="empty-state">Both versions need a ${lang === 'ENG' ? 'English' : 'Malay'} text to compare.</div>`;
        return;
    }
    const results = compareSections(fromList, toList);
    const count = s => results.filter(r => r.status === s).length;
    const counts = { added: count('added'), removed: count('removed'), modified: count('modified'), same: count('same') };
    const changed = counts.added + counts.removed + counts.modified;

    // Amending laws present in the newer version but not the older one, matched on the law reference.
    const known = new Set(from.amendments.map(r => lawKey(r[0])));
    const newAmends = to.amendments.filter(r => !known.has(lawKey(r[0])));
    const fromNotes = to.amendSource === 'notes';

    const chip = (id, label) => `<button type="button" class="chip${filter === id ? ' active' : ''}" data-filter="${id}">${label}</button>`;
    let html = `<div class="chips">
        ${chip('changes', `All changes (${changed})`)}
        ${chip('added', `Added (${counts.added})`)}
        ${chip('removed', `Removed (${counts.removed})`)}
        ${chip('modified', `Modified (${counts.modified})`)}
        ${chip('same', `Unchanged (${counts.same})`)}
    </div>
    <div class="cmp-note">Sections are matched by section number and Schedules by Schedule number. <ins>Inserted</ins> and <del>deleted</del> wording is highlighted.</div>`;
    const ids = Object.keys(actDataStore);
    if (ids.indexOf(fromId) > ids.indexOf(toId)) {
        html = `<div class="cmp-warn">"From" is newer than "To", so additions show as removals. Use <b>&#8644; Swap</b> to compare older to newer.</div>` + html;
    }

    if (fromNotes) {
        html += `<div class="cmp-amend"><h3>Amending laws first cited in ${escapeHtml(to.year)}: ${newAmends.length}</h3>` +
            (newAmends.length
                ? escapeHtml(newAmends.map(r => r[0]).join(', ')) + '<div class="cmp-note" style="padding:6px 0 0">Taken from the [Am. by ...] notes in the text, as this file has no List of Amendments.</div>'
                : 'No new amending laws cited in the notes.') + '</div>';
    } else {
        html += `<div class="cmp-amend"><h3>Amendments new in ${escapeHtml(to.year)}: ${newAmends.length}</h3>` +
            (newAmends.length
                ? '<table>' + newAmends.map(r => '<tr>' + r.map(c => `<td>${escapeHtml(c)}</td>`).join('') + '</tr>').join('') + '</table>'
                : 'No new entries in the List of Amendments.') + '</div>';
    }

    const wanted = r => filter === 'changes' ? r.status !== 'same' : r.status === filter;
    const shown = results.filter(wanted);
    if (!shown.length) html += '<div class="empty-state">Nothing to show for this filter.</div>';
    shown.forEach(r => {
        const { titleHtml, body } = cardParts(r);
        html += `<details class="cmp-card ${r.status}"${r.status === 'same' ? '' : ' open'}>
            <summary><span class="badge ${r.status}">${r.status === 'same' ? 'unchanged' : r.status}</span><span>${titleHtml}</span></summary>
            <div class="cmp-body">${body}</div></details>`;
    });
    out.innerHTML = html;
    out.querySelectorAll('.chip').forEach(c => c.addEventListener('click', () => renderCompare(fromId, toId, lang, c.dataset.filter)));
}

// ---------- Contents sidebar ----------
function buildToc(main) {
    const toc = document.createElement('details');
    toc.className = 'toc';
    toc.open = !window.matchMedia('(max-width: 900px)').matches;
    const summary = document.createElement('summary');
    summary.textContent = 'Contents';
    toc.appendChild(summary);
    const list = document.createElement('div');
    list.className = 'toc-list';

    main.querySelectorAll('.structure-heading, .section-wrapper').forEach(el => {
        const isSection = el.classList.contains('section-wrapper');
        const tag = isSection ? 'sec' : (el.parentElement.dataset.xmlTag || '').toLowerCase();
        const label = isSection
            ? el.querySelector('.section-title').textContent.trim()
            : Array.from(el.querySelectorAll('p')).map(p => p.textContent.trim()).join(' ');
        if (!label) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = `toc-item toc-${tag}`;
        btn.textContent = label;
        btn.addEventListener('click', () => {
            if (isSection) setSection(el, true);
            else if (el.getAttribute('aria-expanded') === 'false') el.click(); // open a Schedule
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        el._tocItem = btn;
        list.appendChild(btn);
    });
    toc.appendChild(list);
    return toc;
}

// ---------- Act view ----------
function switchToAct(actID, dotElement) {
    compareBtn.classList.remove('active');
    document.querySelectorAll('.dot').forEach(d => {
        d.classList.remove('active');
        d.removeAttribute('aria-current');
        d.closest('.node').classList.remove('active');
    });
    dotElement.classList.add('active');
    dotElement.setAttribute('aria-current', 'true');
    dotElement.closest('.node').classList.add('active');
    dotElement.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });

    const data = actDataStore[actID];
    if (!data) return;

    actViewport.innerHTML = '';
    const container = document.createElement('div');

    const sep = document.createElement('div');
    sep.className = 'act-separator';
    sep.textContent = data.name;
    container.appendChild(sep);

    const languages = Array.from(data.doc.querySelectorAll('ENG_LANG, MALAY_LANG'));
    const hasEnglish = languages.some(n => n.tagName === 'ENG_LANG');
    const hasMalay = languages.some(n => n.tagName === 'MALAY_LANG');
    if (!languages.length) {
        container.insertAdjacentHTML('beforeend', '<div class="empty-state">This file has no <code>ENG_LANG</code> or <code>MALAY_LANG</code> content to display.</div>');
        actViewport.appendChild(container);
        return;
    }

    const toolbar = document.createElement('div');
    toolbar.className = 'toolbar';
    toolbar.innerHTML = `
        <div class="language-switch" role="group" aria-label="Language">
            <button type="button" class="language-btn" data-lang="ENG">English</button>
            <button type="button" class="language-btn" data-lang="BM">Malay</button>
        </div>
        <input type="search" class="search-box" placeholder="Search sections..." aria-label="Search sections">
        <button type="button" class="tool-btn" data-act="expand">Expand all</button>
        <button type="button" class="tool-btn" data-act="collapse">Collapse all</button>
        <span class="result-count" aria-live="polite"></span>
    `;
    const englishBtn = toolbar.querySelector('[data-lang="ENG"]');
    const malayBtn = toolbar.querySelector('[data-lang="BM"]');
    englishBtn.disabled = !hasEnglish;
    malayBtn.disabled = !hasMalay;
    container.appendChild(toolbar);

    const panels = {};
    languages.forEach(langNode => {
        const key = langNode.tagName === 'ENG_LANG' ? 'ENG' : 'BM';
        const panel = document.createElement('div');
        panel.className = 'language-panel';
        panel.dataset.language = key;

        const lHeader = document.createElement('div');
        lHeader.className = 'lang-group-header';
        lHeader.textContent = key === 'ENG' ? 'ENGLISH VERSION' : 'VERSI BAHASA MELAYU';
        panel.appendChild(lHeader);

        const layout = document.createElement('div');
        layout.className = 'viewer-layout';
        const main = document.createElement('div');
        main.className = 'viewer-main';
        renderChildren(langNode, main);
        layout.appendChild(buildToc(main));
        layout.appendChild(main);
        panel.appendChild(layout);
        panels[key] = panel;
        container.appendChild(panel);
    });

    const search = toolbar.querySelector('.search-box');
    const counter = toolbar.querySelector('.result-count');
    const activePanel = () => Object.values(panels).find(p => p.classList.contains('active'));

    const applySearch = () => {
        const panel = activePanel();
        if (!panel) return;
        const q = search.value.trim().toLowerCase();
        viewState.query = search.value;
        clearHighlights(panel);
        const sections = panel.querySelectorAll('.section-wrapper');
        let shown = 0;
        sections.forEach(w => {
            const match = !q || w.textContent.toLowerCase().includes(q);
            w.classList.toggle('hidden-by-search', !match);
            w._tocItem?.classList.toggle('hidden-by-search', !match);
            if (match) shown++;
            if (q.length >= 2 && match) {
                highlight(w, q);
                if (w.querySelector('.section-header').getAttribute('aria-expanded') !== 'true') {
                    setSection(w, true);
                    w._autoOpened = true;
                }
            } else if (w._autoOpened) {
                setSection(w, false); // close what the search opened, leave the rest as the reader set it
                w._autoOpened = false;
            }
        });
        // Hide Parts and Schedules with nothing left to show. Deepest groups first.
        Array.from(panel.querySelectorAll('.structure-group')).reverse().forEach(g => {
            const items = g.querySelectorAll('.section-wrapper, .structure-group');
            let visible = !q;
            if (q && items.length) visible = Array.from(items).some(x => !x.classList.contains('hidden-by-search'));
            else if (q) {
                visible = g.textContent.toLowerCase().includes(q);
                if (visible && q.length >= 2) highlight(g, q);
            }
            g.classList.toggle('hidden-by-search', !visible);
            g.querySelector(':scope > .structure-heading')?._tocItem?.classList.toggle('hidden-by-search', !visible);
        });
        counter.textContent = q ? `${shown} of ${sections.length} sections` : `${sections.length} sections`;
    };

    const showLanguage = (key) => {
        Object.values(panels).forEach(panel => panel.classList.remove('active'));
        if (panels[key]) panels[key].classList.add('active');
        englishBtn.classList.toggle('active', key === 'ENG');
        malayBtn.classList.toggle('active', key === 'BM');
        applySearch();
    };
    englishBtn.onclick = () => { viewState.lang = 'ENG'; showLanguage('ENG'); };
    malayBtn.onclick = () => { viewState.lang = 'BM'; showLanguage('BM'); };
    let searchTimer;
    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applySearch, 150);
    });
    search.value = viewState.query;
    toolbar.querySelector('[data-act="expand"]').onclick = () =>
        activePanel()?.querySelectorAll('.section-wrapper:not(.hidden-by-search)').forEach(w => setSection(w, true));
    toolbar.querySelector('[data-act="collapse"]').onclick = () =>
        activePanel()?.querySelectorAll('.section-wrapper').forEach(w => setSection(w, false));

    // Keep the reader's language when this version has it; the choice is not overwritten by the fallback.
    showLanguage(panels[viewState.lang] ? viewState.lang : (hasEnglish ? 'ENG' : 'BM'));
    actViewport.appendChild(container);
}

// ---------- Search highlighting ----------
function highlight(root, q) {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) {
        if (walker.currentNode.nodeValue.toLowerCase().includes(q)) nodes.push(walker.currentNode);
    }
    nodes.forEach(node => {
        const text = node.nodeValue, lower = text.toLowerCase();
        const frag = document.createDocumentFragment();
        let pos = 0, hit;
        while ((hit = lower.indexOf(q, pos)) !== -1) {
            frag.appendChild(document.createTextNode(text.slice(pos, hit)));
            const mark = document.createElement('mark');
            mark.className = 'hit';
            mark.textContent = text.slice(hit, hit + q.length);
            frag.appendChild(mark);
            pos = hit + q.length;
        }
        frag.appendChild(document.createTextNode(text.slice(pos)));
        node.replaceWith(frag);
    });
}

function clearHighlights(root) {
    const parents = new Set();
    root.querySelectorAll('mark.hit').forEach(mark => {
        parents.add(mark.parentNode);
        mark.replaceWith(document.createTextNode(mark.textContent));
    });
    parents.forEach(p => p.normalize());
}

// ---------- Start ----------
if (LV.actId) openFromLibrary(LV.actId, LV.compare);