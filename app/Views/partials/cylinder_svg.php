<?php
$fill = (float)($fill ?? 70);
$width = (int) round(76 * max(0, min(100, $fill)) / 100);
?>
<svg viewBox="0 0 140 300" aria-hidden="true">
    <ellipse cx="70" cy="291" rx="62" ry="8" fill="url(#sh)"/>
    <rect x="24" y="268" width="92" height="16" rx="5" fill="#46554f"/>
    <path d="M20 118C20 78 44 60 70 60s50 18 50 58v142q0 10-10 10H30q-10 0-10-10z" fill="url(#sv)"/>
    <path d="M21 118h98" stroke="#0002"/>
    <rect x="20" y="150" width="100" height="84" fill="url(#em)"/>
    <rect x="20" y="150" width="100" height="4" fill="url(#gd)"/>
    <rect x="20" y="230" width="100" height="4" fill="url(#gd)"/>
    <text x="70" y="181" text-anchor="middle" fill="#F7D06B" font-family="Bricolage Grotesque,sans-serif" font-weight="800" font-size="16">LPG</text>
    <text x="70" y="196" text-anchor="middle" fill="#fff" opacity=".8" font-size="9">11.8 KG</text>
    <rect x="32" y="210" width="76" height="7" rx="3.5" fill="#fff3"/>
    <rect class="lv" x="32" y="210" width="<?= $width ?>" height="7" rx="3.5" fill="url(#gd)"/>
    <rect x="30" y="84" width="9" height="176" rx="4.5" fill="#fff" opacity=".55"/>
    <path d="M40 72V42q0-9 9-9h42q9 0 9 9v30" fill="none" stroke="url(#sv)" stroke-width="6"/>
    <rect x="52" y="44" width="36" height="22" rx="5" fill="url(#gd)"/>
    <rect x="62" y="20" width="16" height="26" rx="3" fill="url(#gd)"/>
    <rect x="54" y="14" width="32" height="8" rx="4" fill="#46554f"/>
    <rect x="84" y="30" width="18" height="7" rx="3" fill="url(#gd)"/>
</svg>
