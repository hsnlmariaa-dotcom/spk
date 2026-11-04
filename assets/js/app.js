/**
 * CDSS-DBD: Clinical Decision Support System - Certainty Factor Method
 * Frontend Logic, Live Inference Engine & Interactive Components
 */

document.addEventListener('DOMContentLoaded', function () {
    initLiveCalculator();
    initSearchFilters();
});

// Default Expert CF & Metadata Lookup (Client-side mirror)
const EXPERT_CF = {
    'G01': 0.80, 'G02': 0.60, 'G03': 0.60, 'G04': 0.70,
    'G05': 0.90, 'G06': 0.50, 'G07': 0.85, 'G08': 0.70,
    'G09': 0.85, 'G10': 0.60, 'G11': 0.95,
    'L01': 0.90, 'L02': 0.90, 'L03': 0.60, 'L04': 0.95, 'L05': 0.90
};

const WARNING_CODES = {
    'G11': 'Tanda Syok Klinis (Dengue Shock Syndrome / DSS) - Kegawatdaruratan Medis!',
    'G07': 'Perdarahan Spontan (Epistaksis / Gusi Berdarah)',
    'G08': 'Nyeri Ulu Hati / Abdomen Berat (Warning Sign WHO)',
    'L01': 'Trombositopenia Berat (< 100.000 /uL)',
    'L02': 'Indikasi Kebocoran Plasma (Hematokrit naik >= 20%)'
};

function initLiveCalculator() {
    const form = document.getElementById('diagnosisForm');
    if (!form) return;

    const radioInputs = form.querySelectorAll('input[type="radio"]');
    radioInputs.forEach(input => {
        input.addEventListener('change', runLiveInference);
    });

    // Run initial inference on load (e.g. for prefilled values or reset)
    runLiveInference();
}

function runLiveInference() {
    const form = document.getElementById('diagnosisForm');
    if (!form) return;

    const checkedInputs = form.querySelectorAll('input[type="radio"]:checked');
    const userInputs = {};
    const presentWarningSigns = [];
    const activeSteps = [];

    checkedInputs.forEach(input => {
        const code = input.name.replace('gejala[', '').replace(']', '');
        const cfUser = parseFloat(input.value) || 0.0;
        userInputs[code] = cfUser;

        // Check warning sign
        if (cfUser >= 0.4 && WARNING_CODES[code]) {
            presentWarningSigns.push({
                code: code,
                message: WARNING_CODES[code]
            });
        }
    });

    // Calculate Certainty Factor Combination
    let runningCF = 0.0;

    Object.keys(EXPERT_CF).forEach(code => {
        const userCF = userInputs[code] || 0.0;
        const expertCF = EXPERT_CF[code] || 0.0;
        const itemCF = userCF * expertCF;

        if (itemCF > 0) {
            const oldCF = runningCF;
            if (runningCF === 0.0) {
                runningCF = itemCF;
            } else {
                runningCF = runningCF + (itemCF * (1.0 - runningCF));
            }

            activeSteps.push({
                code: code,
                userCF: userCF,
                expertCF: expertCF,
                itemCF: itemCF,
                cfBefore: oldCF,
                cfAfter: runningCF
            });
        }
    });

    const finalCF = runningCF;
    const percentage = (finalCF * 100).toFixed(1);

    // Update Visual Gauge
    updateGauge(finalCF, percentage);

    // Update Interpretation Badge & Recommendations
    updateInterpretation(finalCF);

    // Update Warning Signs Banner
    updateWarningBanner(presentWarningSigns);

    // Update WHO Diagnostic Classification
    updateWHOEvaluation(userInputs);

    // Update Live Steps Table
    updateLiveStepsTable(activeSteps);
}

function updateGauge(finalCF, percentage) {
    const gaugeProgress = document.getElementById('gaugeProgress');
    const gaugePercent = document.getElementById('gaugePercent');
    const gaugeRaw = document.getElementById('gaugeRaw');

    if (!gaugeProgress || !gaugePercent) return;

    // Circumference = 2 * PI * r = 2 * 3.14159 * 70 ≈ 440
    const circumference = 440;
    const offset = circumference - (finalCF * circumference);
    gaugeProgress.style.strokeDashoffset = offset;

    // Color transition based on risk
    let strokeColor = '#059669'; // Emerald
    if (finalCF >= 0.80) {
        strokeColor = '#e11d48'; // Rose
    } else if (finalCF >= 0.60) {
        strokeColor = '#d97706'; // Amber
    } else if (finalCF >= 0.40) {
        strokeColor = '#0284c7'; // Primary Blue
    }
    gaugeProgress.style.stroke = strokeColor;

    gaugePercent.innerText = percentage + '%';
    if (gaugeRaw) {
        gaugeRaw.innerText = 'Nilai CF: ' + finalCF.toFixed(4);
    }
}

function updateInterpretation(finalCF) {
    const badge = document.getElementById('interpretationBadge');
    const actionText = document.getElementById('quickActionText');
    if (!badge) return;

    if (finalCF >= 0.80) {
        badge.className = 'badge badge-danger';
        badge.innerText = 'Sangat Mungkin DBD (High Risk)';
        if (actionText) actionText.innerText = 'Prioritas rawat inap & konfirmasi lab lanjutan (NS1/IgM-IgG). Pasang infus cairan IV kristaloid.';
    } else if (finalCF >= 0.60) {
        badge.className = 'badge badge-warning';
        badge.innerText = 'Kemungkinan DBD (Tinggi)';
        if (actionText) actionText.innerText = 'Observasi klinis ketat, rehidrasi oral agresif, kontrol darah rutin serial tiap 12-24 jam.';
    } else if (finalCF >= 0.40) {
        badge.className = 'badge badge-primary';
        badge.innerText = 'Dicurigai DBD (Sedang)';
        if (actionText) actionText.innerText = 'Lakukan pemeriksaan laboratorium darah lengkap dan edukasi tanda bahaya (warning signs).';
    } else if (finalCF > 0.00) {
        badge.className = 'badge badge-success';
        badge.innerText = 'Kemungkinan Kecil DBD (Rendah)';
        if (actionText) actionText.innerText = 'Terapi simtomatik, pantau perkembangan gejala, dan evaluasi diagnosis banding.';
    } else {
        badge.className = 'badge badge-secondary';
        badge.innerText = 'Belum Ada Gejala Dinilai';
        if (actionText) actionText.innerText = 'Silakan pilih tingkat keyakinan pada daftar gejala & parameter lab.';
    }
}

function updateWarningBanner(warnings) {
    const banner = document.getElementById('liveWarningBanner');
    const list = document.getElementById('warningList');
    if (!banner || !list) return;

    if (warnings.length > 0) {
        banner.style.display = 'flex';
        list.innerHTML = warnings.map(w => `<li><strong>${w.code}:</strong> ${w.message}</li>`).join('');
    } else {
        banner.style.display = 'none';
        list.innerHTML = '';
    }
}

function updateWHOEvaluation(u) {
    const whoEl = document.getElementById('whoResultBadge');
    if (!whoEl) return;

    let text = 'Non-DBD / Kriteria Belum Terpenuhi';
    let cls = 'badge-secondary';

    // R4: Syok klinis (G11)
    if (u['G11'] >= 0.4) {
        text = 'DBD Derajat III/IV (Dengue Shock Syndrome / DSS)';
        cls = 'badge-danger';
    }
    // R3: G07 + L01 + L02
    else if (u['G07'] >= 0.4 && u['L01'] >= 0.4 && u['L02'] >= 0.4) {
        text = 'DBD Derajat II (Perdarahan Spontan)';
        cls = 'badge-danger';
    }
    // R1: G01 + G05 + G09 + L01
    else if (u['G01'] >= 0.4 && u['G05'] >= 0.4 && u['G09'] >= 0.4 && u['L01'] >= 0.4) {
        text = 'DBD Derajat I (Kriteria WHO Terpenuhi)';
        cls = 'badge-warning';
    }
    // R2: G01 + G02 + G03 + G04
    else if (u['G01'] >= 0.4 && u['G02'] >= 0.4 && u['G03'] >= 0.4 && u['G04'] >= 0.4) {
        text = 'Suspek Demam Dengue (Dengue Fever / DF)';
        cls = 'badge-primary';
    }

    whoEl.className = 'badge ' + cls;
    whoEl.innerText = text;
}

function updateLiveStepsTable(steps) {
    const tableBody = document.getElementById('liveStepsBody');
    const countEl = document.getElementById('activeEvidenceCount');
    if (!tableBody) return;

    if (countEl) countEl.innerText = steps.length;

    if (steps.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada gejala aktif yang dinilai (pilih "Tidak tahu" hingga "Sangat yakin").</td></tr>`;
        return;
    }

    tableBody.innerHTML = steps.map((s, idx) => `
        <tr>
            <td style="font-family: var(--font-mono); font-size: 0.8rem; font-weight: 700; color: var(--primary);">${s.code}</td>
            <td style="color: var(--text-secondary); font-weight: 600;">${s.userCF.toFixed(1)}</td>
            <td style="color: var(--text-secondary); font-weight: 600;">${s.expertCF.toFixed(2)}</td>
            <td style="font-weight: 700; color: var(--emerald);">${s.itemCF.toFixed(4)}</td>
            <td style="color: var(--text-muted); font-size: 0.8rem;">${idx === 0 ? '0.000000' : s.cfBefore.toFixed(6)}</td>
            <td style="font-family: var(--font-mono); font-weight: 700; color: var(--primary);">${s.cfAfter.toFixed(6)}</td>
        </tr>
    `).join('');
}

function initSearchFilters() {
    const searchInput = document.getElementById('tableSearchInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', function () {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.filterable-table tbody tr');

        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
