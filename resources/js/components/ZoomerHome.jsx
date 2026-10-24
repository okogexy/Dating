import React, { useEffect, useRef, useState } from 'react';
import { ArrowDown, ArrowUpRight, Heart, Pause, Play, Sparkles } from 'lucide-react';

const phrases = [
    { prefix: 'НАЙДИ СВОЮ', word: 'АЛЬТУШКУ.' },
    { prefix: 'НАЙДИ СВОЕГО', word: 'КРАША.' },
    { prefix: 'НАЙДИ СВОЙ', word: 'ПЛЮС ОДИН.' },
];
const photoColumns = [
    ['alt-campaign.webp', 'alice-portrait.webp'],
    ['street-campaign.webp', 'mark-portrait.webp'],
    ['lera-portrait.webp', 'sofia-portrait.webp'],
    ['anna-portrait.webp', 'daniel-portrait.webp'],
];
const moods = [
    { label: 'Мрачный плейлист', name: 'Мира', age: 25, photo: 'alt-campaign.webp', bio: 'Сначала скинь треки. Потом решим, куда гулять.', tag: 'музыка на максималках', message: 'у тебя тоже этот трек на репите?' },
    { label: 'Рилсы в 03:00', name: 'Никита', age: 26, photo: 'street-campaign.webp', bio: 'Мой язык любви — «ахах, это мы». Вложения: 48 рилсов.', tag: 'смеёмся с одной фигни', message: 'мы? мы.' },
    { label: 'Спонтанно погнали', name: 'София', age: 25, photo: 'sofia-portrait.webp', bio: '«Выйду на полчасика» — и вот мы уже в другом районе.', tag: 'планы переоценены', message: 'кофе через 20 минут?' },
];
const dogLines = ['я тебе не психолог', 'ладно, ты норм', 'одобряю. иди к людям'];

function useReducedMotion() {
    const [reduced, setReduced] = useState(() => window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = () => setReduced(query.matches);
        query.addEventListener('change', update);
        return () => query.removeEventListener('change', update);
    }, []);
    return reduced;
}

export default function ZoomerHome({ signedIn, onRegister }) {
    const [phrase, setPhrase] = useState(0);
    const [paused, setPaused] = useState(false);
    const [mood, setMood] = useState(0);
    const [dogPets, setDogPets] = useState(0);
    const reduced = useReducedMotion();
    const main = useRef(null);
    const motion = !paused && !reduced;
    const currentMood = moods[mood];

    useEffect(() => {
        if (!motion) return;
        const timer = setInterval(() => {
            if (document.visibilityState === 'visible') setPhrase(value => (value + 1) % phrases.length);
        }, 5200);
        return () => clearInterval(timer);
    }, [motion]);

    useEffect(() => {
        const root = main.current;
        if (!motion || !('IntersectionObserver' in window)) return;
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        root.classList.add('motion-ready');
        root.querySelectorAll('.zm-reveal').forEach(element => observer.observe(element));
        return () => { observer.disconnect(); root.classList.remove('motion-ready'); };
    }, [motion]);

    const moveWall = event => {
        if (!motion || event.pointerType !== 'mouse') return;
        const bounds = event.currentTarget.getBoundingClientRect();
        event.currentTarget.style.setProperty('--pointer-x', `${((event.clientX - bounds.left) / bounds.width - 0.5) * 16}px`);
        event.currentTarget.style.setProperty('--pointer-y', `${((event.clientY - bounds.top) / bounds.height - 0.5) * 10}px`);
    };

    return <main id="main-content" className="zm-main" ref={main} data-motion={motion ? 'on' : 'off'}>
        <section className="zm-hero" aria-labelledby="landing-title" onPointerMove={moveWall} onPointerLeave={event => { event.currentTarget.style.setProperty('--pointer-x', '0px'); event.currentTarget.style.setProperty('--pointer-y', '0px'); }}>
            <div className="zm-wall" aria-hidden="true">{photoColumns.map((column, index) => <div className={`zm-wall-column column-${index}`} key={index}>{column.map((photo, photoIndex) => <div className="zm-wall-photo" key={photo}><img src={'/images/campaign/' + photo} alt="" fetchPriority={index === 1 && photoIndex === 0 ? 'high' : 'auto'} decoding="async"/></div>)}</div>)}</div>
            <div className="zm-hero-shade"/>
            <div className="zm-sticker zm-sticker-pink" aria-hidden="true">твоя новая<br/>фиксация <Heart size={16} fill="currentColor"/></div>
            <div className="zm-sticker zm-sticker-blue" aria-hidden="true">«чел, это мэтч»<span>♥</span></div>
            <div className="zm-hero-content">
                <div className="zm-hero-kicker"><span className="zm-live-dot"/>ЗНАКОМСТВА ДЛЯ ТЕХ, КТО В ТЕМЕ<span className="zm-age">18+</span></div>
                <h1 id="landing-title" className="zm-hero-title"><span key={'prefix-' + phrase} className="zm-phrase-prefix">{phrases[phrase].prefix}</span><strong key={phrase}>{phrases[phrase].word}</strong></h1>
                <p className="zm-hero-copy">Для отношений, дружбы и совместного<br className="zm-desktop-break"/> «смотри, какой рилс».</p>
                {signedIn ? <a className="zm-primary" href="/cabinet">К анкетам<ArrowUpRight size={21}/></a> : <button className="zm-primary" onClick={onRegister} aria-label="Создать анкету">Ладно, погнали<ArrowUpRight size={21}/></button>}
                <span className="zm-cta-note">анкета без собеседования</span>
            </div>
            <div className="zm-hero-bottom"><a href="#dog-witness"><ArrowDown size={18}/>ниже — лор</a>{!reduced && <button className="zm-motion" onClick={() => setPaused(!paused)} aria-label={paused ? 'Включить анимацию' : 'Поставить анимацию на паузу'} aria-pressed={paused}>{paused ? <Play size={13}/> : <Pause size={13}/>}<span>{paused ? 'Оживить' : 'Пауза'}</span></button>}</div>
        </section>

        <div className="zm-ticker" aria-hidden="true"><div className="zm-ticker-track">{[0, 1].map(copy => <span key={copy}>АЛЬТУШКА <i>✳</i> СОУЛМЕЙТ <i>✳</i> +1 НА КОНЦЕРТ <i>✳</i> ТВОЙ ЧЕЛОВЕК <i>✳</i> ОБЩИЙ КРИНЖ <i>✳</i> </span>)}</div></div>

        <section id="dog-witness" className="zm-dog-section" aria-labelledby="dog-title">
            <div className="zm-dog-copy zm-reveal"><span className="zm-section-label">ПОКА ТЫ ДУМАЕШЬ, КОМУ БЫ НАПИСАТЬ</span><h2 id="dog-title">ПЁС УЖЕ<br/>ЗНАЕТ ВЕСЬ<br/><span>ТВОЙ ЛОР.</span></h2><p>«Сегодня я опять без пары…» — ты.<br/>«Брат, я просил погулять» — твой пёс.</p><span className="zm-dog-aside">Может, дадим ему выходной от твоей личной жизни?</span></div>
            <div className={`zm-dog-stage zm-reveal ${dogPets ? 'is-petted' : ''}`}>
                <div className="zm-dog-halo" aria-hidden="true"/>
                <span key={'line-' + dogPets} className="zm-dog-bubble" aria-live="polite">{dogLines[Math.min(dogPets, dogLines.length - 1)]}</span>
                <img className="zm-dog-image" src="/images/campaign/dog-witness.webp" alt="Мемный пёс с укоризненным взглядом и розовой банданой" loading="lazy"/>
                {dogPets > 0 && <div className="zm-heart-burst" aria-hidden="true" key={dogPets}>{[0, 1, 2, 3, 4].map(index => <Heart key={index} size={20 + index * 3} fill="currentColor" style={{ '--heart-index': index }}/>)}</div>}
                <button className="zm-pet-button" onClick={() => setDogPets(value => value + 1)} aria-label="Погладить пса"><Heart size={18} fill={dogPets ? 'currentColor' : 'none'}/>{dogPets ? 'пёс одобряет' : 'погладить свидетеля'}</button>
            </div>
        </section>

        <section id="how-it-works" className="zm-moods-section" aria-labelledby="moods-title">
            <div className="zm-mood-intro zm-reveal"><span className="zm-section-label">СОВПАСТЬ МОЖНО ДАЖЕ ПО КРИНЖУ</span><h2 id="moods-title">КОМУ-ТО<br/>ЗАЙДЁТ ТВОЁ<br/><span>«НУ Я ТАКОЙ».</span></h2><p>Мрачный плейлист? Рилсы вместо признаний?<br/>Есть люди, которым именно это и надо.</p><div className="zm-mood-options" role="group" aria-label="Выбрать настроение примера">{moods.map((item, index) => <button key={item.label} aria-pressed={mood === index} className={mood === index ? 'active' : ''} onClick={() => setMood(index)}><Sparkles size={15}/>{item.label}<ArrowUpRight size={16}/></button>)}</div></div>
            <div className="zm-mood-preview zm-reveal"><div className="zm-example-tag">ПРИМЕР АНКЕТЫ</div><article className="zm-example-card" key={currentMood.name}><img src={'/images/campaign/' + currentMood.photo} alt={`Иллюстративная анкета: ${currentMood.name}`} loading="lazy"/><div className="zm-example-shade"/><span className="zm-example-interest">{currentMood.tag}</span><div className="zm-example-person"><h3>{currentMood.name}, {currentMood.age}</h3><p>{currentMood.bio}</p></div><div className="zm-example-heart" aria-hidden="true"><Heart size={26} fill="currentColor"/></div></article><div className="zm-example-message" key={currentMood.message}><span>{currentMood.message}</span><span aria-hidden="true">♡</span></div></div>
        </section>
        <div className="zm-fine-print">Люди на фото вымышлены. В кабинете можно создать свою анкету и познакомиться по-настоящему.</div>
    </main>;
}

