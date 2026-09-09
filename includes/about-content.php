<?php
/**
 * Editable About-page content.
 *
 * Every block on /about is stored as a site setting so administrators can
 * rewrite it from Admin > About Page without touching code. Anything that has
 * never been edited falls back to the built-in copy below, so the page reads
 * correctly on a fresh install.
 *
 * i18n INTERACTION (important)
 * ----------------------------
 * includes/i18n.php replaces the innerHTML of every [data-i18n] element with
 * the dictionary value for that key. If we emitted data-i18n unconditionally,
 * the translation would immediately overwrite whatever an admin had typed.
 *
 * So about_text() emits the data-i18n attribute ONLY while a field is still on
 * its built-in default. The moment an admin saves custom copy, the attribute is
 * dropped and their text stands. Untouched fields keep full en/sw/fr support.
 */

/** Simple text blocks: setting key => [i18n key, default copy]. */
function about_defaults(): array {
    return [
        // Hero
        'hero_badge'   => ['about.badge',        'About CRTP'],
        'hero_title1'  => ['about.heroTitle1',   'Reflecting on Our'],
        'hero_title2'  => ['about.heroTitle2',   'Shared Future'],
        'hero_desc'    => ['about.heroDesc',     'The Centre for Research, Training and Policy (CRTP) is an independent research and capacity-building organization committed to advancing peace, security, and good governance across Africa. Tafakari — meaning "to reflect" in Swahili — is our digital platform connecting research, media, and community.'],
        'hero_cta1'    => ['about.partnerWithUs','Partner With Us'],
        'hero_cta2'    => ['about.viewHeatmap',  'View Conflict Heatmap'],

        // Purpose
        'purpose_badge' => ['about.purposeBadge', 'Our Purpose'],
        'purpose_title' => ['about.purposeTitle', 'Why CRTP Exists'],
        'purpose_desc'  => ['about.purposeDesc',  'CRTP exists to give African institutions, communities, and leaders a trustworthy source of rigorous research and practical capacity-building support — so that decisions on peace, security, and governance are grounded in evidence, not guesswork.'],

        // Mission & Vision
        'mission_title' => ['about.missionTitle', 'Our Mission'],
        'mission_desc'  => ['about.missionDesc',  'To generate rigorous, policy-relevant research and provide capacity-building support that empowers institutions, communities, and leaders to build sustainable peace and advance human security across Africa.'],
        'vision_title'  => ['about.visionTitle',  'Our Vision'],
        'vision_desc'   => ['about.visionDesc',   'An Africa where knowledge-driven decision-making, inclusive dialogue, and accountable governance create the conditions for lasting peace and equitable development — leaving no community behind.'],

        // Programme areas
        'programmes_badge' => ['about.whatWeDoBadge',        'What We Do'],
        'programmes_title' => ['about.programmeAreasTitle',  'Core Programme Areas'],

        // Geographic focus
        'geo_badge' => ['about.whereWeWorkBadge', 'Where We Work'],
        'geo_title' => ['about.geoFocusTitle',    'Geographic Focus'],
        'geo_desc'  => ['about.geoFocusDesc',     'Our primary focus spans East Africa and the Great Lakes region, with expanding coverage across the Sahel and Horn of Africa — regions experiencing the most complex and intersecting conflict dynamics on the continent.'],
        'geo_cta'   => ['about.exploreHeatmap',   'Explore Interactive Heatmap'],

        // Values
        'values_badge' => ['about.foundationBadge', 'Our Foundation'],
        'values_title' => ['about.valuesTitle',     'Guiding Values'],

        // Team
        'team_badge' => ['about.peopleBadge', 'The People'],
        'team_title' => ['about.teamTitle',   'Our Team'],

        // Closing call to action
        'cta_title' => ['about.ctaTitle',      'Ready to collaborate?'],
        'cta_desc'  => ['about.ctaDesc',       'Whether you are a researcher, journalist, policymaker, or community leader — there is a place for your expertise in our network.'],
        'cta_btn1'  => ['about.getInTouch',    'Get In Touch'],
        'cta_btn2'  => ['about.readResearch',  'Read Our Research'],
    ];
}

/** Default repeater rows, used until an admin saves their own. */
function about_default_lists(): array {
    return [
        'programmes' => [
            ['num'=>'01','title'=>'Peace & Security Research','desc'=>'Conflict analysis, early-warning systems, and field-based research across active and post-conflict zones. Our studies inform UN peacekeeping operations, NGO interventions, and government policy.','tags'=>['Conflict Analysis','Early Warning','Field Research']],
            ['num'=>'02','title'=>'Governance & Policy','desc'=>'Monitoring state fragility, electoral integrity, institutional reform, and anti-corruption measures. We translate complex policy environments into actionable recommendations.','tags'=>['State Fragility','Electoral Integrity','Policy Briefs']],
            ['num'=>'03','title'=>'Capacity Building & Training','desc'=>'Structured training programmes for journalists, civil society organizations, government officials, and community leaders on conflict-sensitive reporting, peacebuilding, and advocacy.','tags'=>['Journalism Training','CSO Support','Advocacy Skills']],
            ['num'=>'04','title'=>'Knowledge Management & Media','desc'=>'The Tafakari platform serves as our digital knowledge hub — aggregating research, broadcasting field stories through podcasts and video, and providing open-access document archives.','tags'=>['Open Access','Podcast','Digital Media']],
        ],
        'values' => [
            ['letter'=>'R','title'=>'Rigour','desc'=>'Evidence-based methodology underpins all our research. We hold ourselves to the highest academic and professional standards.'],
            ['letter'=>'I','title'=>'Independence','desc'=>'We operate free from political affiliation or donor bias. Our findings reflect the evidence, not agendas.'],
            ['letter'=>'I','title'=>'Inclusion','desc'=>'Community voices are as vital as academic expertise. We amplify perspectives that are often overlooked in formal policy spaces.'],
            ['letter'=>'I','title'=>'Impact','desc'=>'Research without action is incomplete. We design every project with measurable policy, community, or behavioural outcomes in mind.'],
        ],
        'geo' => [
            ['region'=>'East Africa','countries'=>'Kenya · Uganda · Tanzania · Rwanda · Burundi','tag'=>'Primary'],
            ['region'=>'Great Lakes','countries'=>'DR Congo · Rwanda · Burundi · South Sudan','tag'=>'Primary'],
            ['region'=>'Horn of Africa','countries'=>'Ethiopia · Somalia · Eritrea · Djibouti · Sudan','tag'=>'Active'],
            ['region'=>'Sahel Region','countries'=>'Mali · Niger · Burkina Faso · Chad · Nigeria NE','tag'=>'Expanding'],
            ['region'=>'Southern Africa','countries'=>'Mozambique · Zimbabwe · Zambia · Madagascar','tag'=>'Expanding'],
            ['region'=>'Central Africa','countries'=>'Central African Republic · Cameroon · Congo','tag'=>'Monitoring'],
        ],
    ];
}

/** Tag styles available on the Geographic Focus cards. */
function about_geo_tags(): array {
    return ['Primary', 'Active', 'Expanding', 'Monitoring'];
}

/**
 * One editable text block.
 *
 * @return array{text:string,i18n:string,custom:bool}
 *         `i18n` is a ready-to-print attribute, empty once the field is custom.
 */
function about_text(string $key): array {
    $defs = about_defaults();
    if (!isset($defs[$key])) return ['text' => '', 'i18n' => '', 'custom' => false];
    [$i18nKey, $default] = $defs[$key];

    $stored = trim(get_setting('about_' . $key, ''));
    if ($stored !== '') {
        // Custom copy: drop data-i18n so the translation layer cannot overwrite it
        return ['text' => $stored, 'i18n' => '', 'custom' => true];
    }
    return [
        'text'   => $default,
        'i18n'   => ' data-i18n="' . htmlspecialchars($i18nKey, ENT_QUOTES) . '"',
        'custom' => false,
    ];
}

/** Convenience: just the text of an editable block. */
function about_str(string $key): string {
    return about_text($key)['text'];
}

/**
 * One editable repeater list ('programmes' | 'values' | 'geo').
 * Falls back to the built-in rows when nothing valid is stored.
 */
function about_list(string $key): array {
    $stored = trim(get_setting('about_list_' . $key, ''));
    if ($stored !== '') {
        $rows = json_decode($stored, true);
        if (is_array($rows) && $rows) return $rows;
    }
    return about_default_lists()[$key] ?? [];
}
