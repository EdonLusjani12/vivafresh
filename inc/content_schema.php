<?php
/*
 * Every editable piece of the public site. The admin panel builds its forms from this list,
 * and these defaults are shown until something is saved in the admin.
 * Types: text, textarea, image, url, icon (Font Awesome name, e.g. fa-leaf).
 */

function cfield(string $label, string $type, string $default, string $help = ''): array
{
    return ['label' => $label, 'type' => $type, 'default' => $default, 'help' => $help];
}

$iconHelp = 'Име на Font Awesome икона, пр. fa-leaf, fa-tag, fa-users (fontawesome.com/icons).';

return [
    'site' => [
        'label' => 'Општо',
        'description' => 'Лого, контакт и линкови што се прикажуваат на сите страници.',
        'fields' => [
            'site.logo' => cfield('Лого', 'image', 'Logo.png'),
            'site.name' => cfield('Име на сајтот', 'text', 'Viva Fresh Store MK', 'Се користи во насловот на табот и во футерот.'),
            'site.brand' => cfield('Име во менито', 'text', 'Viva Fresh'),
            'site.brand_suffix' => cfield('Додаток на името (зелено)', 'text', 'MK'),
            'site.phone' => cfield('Телефон', 'text', '071 350 288'),
            'site.phone_intl' => cfield('Телефон во меѓународен формат', 'text', '38971350288', 'Само бројки, без + и празни места. Се користи за WhatsApp и повик.'),
            'site.email' => cfield('Е-пошта', 'text', 'info@vivafresh.mk'),
            'site.address' => cfield('Адреса / град', 'text', 'Скопје, Македонија'),
            'site.hours' => cfield('Работно време', 'text', 'Пон–Саб 07:00–22:00 · Нед 10:00–20:00'),
            'site.instagram' => cfield('Instagram линк', 'url', 'https://www.instagram.com/vivafreshmk/'),
            'site.facebook' => cfield('Facebook линк', 'url', 'https://www.facebook.com/freshstoremk'),
            'site.footer_text' => cfield('Текст во футерот', 'textarea', 'Свежи производи по фер цени во Скопје.'),
        ],
    ],
    'home' => [
        'label' => 'Почетна',
        'description' => 'Почетната страница.',
        'fields' => [
            'home.kicker' => cfield('Мал наслов над насловот', 'text', 'Свежо • Локално • Секој ден'),
            'home.title' => cfield('Наслов (прв ред)', 'text', 'Свежа храна,'),
            'home.title_em' => cfield('Наслов (втор ред, зелено)', 'text', 'по фер цена'),
            'home.lede' => cfield('Опис', 'textarea', 'Овошје, зеленчук и секојдневни производи од маркетите Viva Fresh во Скопје. Квалитет што се гледа, цени што се чувствуваат.'),
            'home.open_note' => cfield('Истакнат ред (работно време)', 'text', 'Отворено денес · 07:00–22:00'),
            'home.hero_image' => cfield('Главна слика', 'image', 'desktop.jpg'),
            'home.stat1_value' => cfield('Бројка 1', 'text', '2'),
            'home.stat1_label' => cfield('Бројка 1 – опис', 'text', 'маркети во Скопје'),
            'home.stat2_value' => cfield('Бројка 2', 'text', '7–22'),
            'home.stat2_label' => cfield('Бројка 2 – опис', 'text', 'часови понеделник–сабота'),
            'home.stat3_value' => cfield('Бројка 3', 'text', '10–20'),
            'home.stat3_label' => cfield('Бројка 3 – опис', 'text', 'недела'),
            'home.stat4_value' => cfield('Бројка 4', 'text', '071 350 288'),
            'home.stat4_label' => cfield('Бројка 4 – опис', 'text', 'Viber / WhatsApp'),
            'home.why_title' => cfield('Секција „Зошто“ – наслов', 'text', 'Зошто Viva Fresh'),
            'home.why_text' => cfield('Секција „Зошто“ – опис', 'text', 'Кратко, јасно и свежо — без бучен дизајн од 2015.'),
            'home.card1_icon' => cfield('Картичка 1 – икона', 'icon', 'fa-leaf', $iconHelp),
            'home.card1_title' => cfield('Картичка 1 – наслов', 'text', 'Свежа стока'),
            'home.card1_text' => cfield('Картичка 1 – текст', 'textarea', 'Дневна достава на овошје и зеленчук од локални производители.'),
            'home.card2_icon' => cfield('Картичка 2 – икона', 'icon', 'fa-tag', $iconHelp),
            'home.card2_title' => cfield('Картичка 2 – наслов', 'text', 'Реални цени'),
            'home.card2_text' => cfield('Картичка 2 – текст', 'textarea', 'Промоции и ценовници што може да ги проверите во секој момент.'),
            'home.card3_icon' => cfield('Картичка 3 – икона', 'icon', 'fa-recycle', $iconHelp),
            'home.card3_title' => cfield('Картичка 3 – наслов', 'text', 'Too Good To Go'),
            'home.card3_text' => cfield('Картичка 3 – текст', 'textarea', 'Заштедете храна и пари со понудите што остануваат на крајот од денот.'),
            'home.video_title' => cfield('Видео – наслов', 'text', 'Добредојдовте во продавницата'),
            'home.video_text' => cfield('Видео – опис', 'text', 'Кратко видео за тоа како изгледа Viva Fresh.'),
            'home.video_url' => cfield('YouTube линк', 'url', 'https://www.youtube.com/watch?v=VK4yqmTIG-4', 'Оставете празно за да се сокрие видеото.'),
            'home.contact_title' => cfield('Контакт – наслов', 'text', 'Дали ви треба помош?'),
            'home.contact_text' => cfield('Контакт – текст', 'text', 'Пишете ни на Viber или WhatsApp — одговараме брзо.'),
        ],
    ],
    'katalog' => [
        'label' => 'Каталог',
        'description' => 'Страницата со каталогот.',
        'fields' => [
            'katalog.kicker' => cfield('Мал наслов', 'text', 'Актуелен каталог'),
            'katalog.title' => cfield('Наслов', 'text', 'Производи'),
            'katalog.title_em' => cfield('Наслов (зелено)', 'text', 'оваа недела'),
            'katalog.text' => cfield('Опис', 'textarea', 'Супер цени, супер избор за секој ден. Super çmime, super zgjedhje për çdo ditë.'),
            'katalog.url' => cfield('Линк до каталогот (FlipHTML5)', 'url', 'https://online.fliphtml5.com/yxxvg/estu/', 'Линкот од FlipHTML5 за новиот каталог.'),
            'katalog.contact_title' => cfield('Контакт – наслов', 'text', 'Прашање за производ?'),
        ],
    ],
    'cenovnik' => [
        'label' => 'Ценовници',
        'description' => 'Текстовите на страницата со ценовници. Маркетите се менуваат во „Маркети“.',
        'fields' => [
            'cenovnik.kicker' => cfield('Мал наслов', 'text', 'Цени'),
            'cenovnik.title' => cfield('Наслов', 'text', 'Ценовници по'),
            'cenovnik.title_em' => cfield('Наслов (зелено)', 'text', 'маркет'),
            'cenovnik.text' => cfield('Опис', 'textarea', 'Изберете маркет за да ги видите производите и промоциите.'),
        ],
    ],
    'kontakt' => [
        'label' => 'Контакт',
        'description' => 'Страницата со локации и контакт.',
        'fields' => [
            'kontakt.kicker' => cfield('Мал наслов', 'text', 'Каде сме'),
            'kontakt.title' => cfield('Наслов', 'text', 'Два маркета'),
            'kontakt.title_em' => cfield('Наслов (зелено)', 'text', 'во Скопје'),
            'kontakt.text' => cfield('Опис', 'textarea', 'Посетете нè или пишете на 071 350 288.'),
            'kontakt.map1_title' => cfield('Мапа 1 – име', 'text', 'Viva Fresh Store 1'),
            'kontakt.map1_url' => cfield('Мапа 1 – Google Maps embed линк', 'url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d5927.837425161794!2d21.42646038590648!3d42.02347618225961!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x135415e267979317%3A0x4f01c4c52fa438a6!2sSupermarket%20Viva%20Fresh%20Store!5e0!3m2!1sen!2smk!4v1747052921918!5m2!1sen!2smk', 'Google Maps → Сподели → Вметни мапа → копирајте го само линкот од src="...". Оставете празно за да се сокрие.'),
            'kontakt.map2_title' => cfield('Мапа 2 – име', 'text', 'Viva Fresh Store 2'),
            'kontakt.map2_url' => cfield('Мапа 2 – Google Maps embed линк', 'url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2964.009348966901!2d21.44982937074397!3d42.02153184397938!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x135415003a11c157%3A0x1d47217a58c40038!2sVivaFreshStore!5e0!3m2!1sen!2smk!4v1747053079902!5m2!1sen!2smk', 'Оставете празно за да се сокрие.'),
            'kontakt.panel_title' => cfield('Контакт – наслов', 'text', 'Контакт'),
        ],
    ],
    'vrabotuvanje' => [
        'label' => 'Вработување',
        'description' => 'Текстовите на страницата за вработување. Позициите се менуваат во „Позиции“.',
        'fields' => [
            'vrabotuvanje.kicker' => cfield('Мал наслов', 'text', 'Кариера'),
            'vrabotuvanje.title' => cfield('Наслов', 'text', 'Придружи се на'),
            'vrabotuvanje.title_em' => cfield('Наслов (зелено)', 'text', 'тимот'),
            'vrabotuvanje.text' => cfield('Опис', 'textarea', 'Пополни ги податоците и прикачи CV. Ќе те контактираме кога ќе има соодветна позиција.'),
            'vrabotuvanje.benefit1_icon' => cfield('Бенефит 1 – икона', 'icon', 'fa-users', $iconHelp),
            'vrabotuvanje.benefit1_title' => cfield('Бенефит 1 – наслов', 'text', 'Тим'),
            'vrabotuvanje.benefit1_text' => cfield('Бенефит 1 – текст', 'textarea', 'Работна средина каде секој придонес се вреднува.'),
            'vrabotuvanje.benefit2_icon' => cfield('Бенефит 2 – икона', 'icon', 'fa-graduation-cap', $iconHelp),
            'vrabotuvanje.benefit2_title' => cfield('Бенефит 2 – наслов', 'text', 'Развој'),
            'vrabotuvanje.benefit2_text' => cfield('Бенефит 2 – текст', 'textarea', 'Обуки и можност за напредок.'),
            'vrabotuvanje.benefit3_icon' => cfield('Бенефит 3 – икона', 'icon', 'fa-heartbeat', $iconHelp),
            'vrabotuvanje.benefit3_title' => cfield('Бенефит 3 – наслов', 'text', 'Бенефити'),
            'vrabotuvanje.benefit3_text' => cfield('Бенефит 3 – текст', 'textarea', 'Плата, попусти на производи и здравствено осигурување.'),
            'vrabotuvanje.positions_title' => cfield('Отворени позиции – наслов', 'text', 'Отворени позиции'),
            'vrabotuvanje.positions_empty' => cfield('Текст кога нема позиции', 'textarea', 'Моментално нема отворени позиции, но можете да испратите општа апликација.'),
            'vrabotuvanje.form_title' => cfield('Формулар – наслов', 'text', 'Апликација'),
        ],
    ],
];
