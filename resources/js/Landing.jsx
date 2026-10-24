import React, { useState } from 'react';
import { ArrowDown, ArrowUpRight, Plus, Minus, LoaderCircle } from 'lucide-react';
import { AuthModal, Brand, Flower, Modal } from './components/Shared';

const questions = [
    ['Мне обязательно искать отношения?', 'Нет. В анкете можно выбрать отношения, дружбу или общение. А в фильтрах — найти людей с той же целью.'],
    ['Кто может мне написать?', 'Только человек, которому вы тоже поставили лайк. Взаимная симпатия открывает чат — до этого сообщений не будет.'],
    ['А если хочется сделать паузу?', 'Скройте анкету в настройках. Она исчезнет из подборок, а переписки сохранятся. Если кто-то мешает — его можно заблокировать.'],
];

export default function Landing() {
    const signedIn = document.querySelector('meta[name="viewer-authenticated"]')?.content === '1';
    const [modal, setModal] = useState(null);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const open = mode => { setError(''); setModal(mode); };
    const close = () => { if (!busy) { setModal(null); setError(''); } };
    const run = async action => {
        if (busy) return;
        setBusy(true);
        setError('');
        try { await action(); } catch (e) { setError(e.message); } finally { setBusy(false); }
    };
    const enterCabinet = () => { location.assign('/cabinet'); };

    return <div className="landing">
        <a className="skip-to-content" href="#main-content">К содержимому</a>
        <header className="landing-header">
            <Brand/>
            <nav aria-label="О проекте"><a href="#how-it-works">Как это устроено</a><a href="#questions">Вопросы</a></nav>
            {signedIn ? <span className="landing-session"><i/>Вы вошли</span> : <button className="landing-sign-in" onClick={() => open('login')}>Войти<ArrowUpRight size={16}/></button>}
        </header>

        <main id="main-content">
            <section className="landing-hero" aria-labelledby="landing-title">
                <div className="hero-index"><span>ЗНАКОМСТВА<br/>С ПРОДОЛЖЕНИЕМ</span><span>18+</span></div>
                <h1 id="landing-title" className="hero-title">ХОЧЕТСЯ<br/>КОГО-ТО<br/><span>СВОЕГО.</span></h1>
                <figure className="hero-portrait"><img src="/images/alice-portrait.png" alt="Иллюстративный портрет девушки в зелёном свитере" fetchPriority="high"/><figcaption>У каждого знакомства свой характер.</figcaption></figure>
                <div className="hero-invitation">
                    <p>Здесь знакомятся по одному.<br/>Если понравились друг другу —<br/>можно написать.</p>
                    {signedIn ? <a className="circle-cta" href="/cabinet"><span>К анкетам</span><ArrowUpRight size={23}/></a> : <button className="circle-cta" onClick={() => open('register')} aria-label="Создать анкету"><span>Создать<br/>анкету</span><ArrowUpRight size={23}/></button>}
                </div>
                <figure className="hero-second-portrait"><img src="/images/mark-portrait.png" alt="Иллюстративный портрет парня на прогулке" decoding="async"/></figure>
                <Flower color="pink" className="hero-flower"/>
                <div className="hero-bottom"><a href="#how-it-works" aria-label="Узнать, как работает Связь"><ArrowDown size={22}/></a></div>
            </section>

            <section className="landing-how" id="how-it-works" aria-labelledby="how-title">
                <div className="how-heading"><span className="editorial-label">01 / КАК ЭТО УСТРОЕНО</span><h2 id="how-title">НАЧНИ<br/>С ПРОСТОГО<span>.</span></h2></div>
                <div className="how-content">
                    <div className="how-steps">
                        <article><span className="step-number">01</span><div><h3>Пару слов о себе</h3><p>Фото из обычной жизни, город и то, чем увлекаешься. Анкете не нужно быть идеальной.</p></div></article>
                        <article><span className="step-number">02</span><div><h3>Один человек за раз</h3><p>Посмотри анкету. Зацепило — поставь лайк. Не твоё — знакомься дальше.</p></div></article>
                        <article><span className="step-number">03</span><div><h3>Есть симпатия? Поговорите.</h3><p>Если выбор взаимный, откроется чат. Дальше решайте сами: переписываться или идти пить кофе.</p></div></article>
                    </div>
                    <figure className="how-portrait"><img src="/images/sofia-portrait.png" alt="Иллюстративный портрет девушки в мастерской" loading="lazy"/><span className="how-photo-note">МОЖНО<br/>ПРОСТО<br/><i>БЫТЬ СОБОЙ.</i></span></figure>
                </div>
                <div className="how-caption"><span>Уважение — с первого сообщения.</span><p>На главной — вымышленные люди.<br/>Настоящие знакомства начинаются в кабинете.</p></div>
            </section>
        </main>

        <footer className="landing-footer" id="questions">
            <div className="footer-questions">
                <div><span className="editorial-label">02 / ПЕРЕД ЗНАКОМСТВОМ</span><h2>БЕЗ<br/>НЕЛОВКИХ<br/><span>ВОПРОСОВ.</span></h2><p>Понятные правила.<br/>И право выбирать свой темп.</p></div>
                <div className="questions-list">{questions.map(([title, text]) => <details key={title} name="landing-questions"><summary><span>{title}</span><Plus className="question-plus" size={19}/><Minus className="question-minus" size={19}/></summary><p>{text}</p></details>)}</div>
            </div>
            <div className="footer-signature"><div className="footer-wordmark" aria-label="Связь">связь<span>.</span></div><Flower color="orange" className="footer-mascot"/></div>
            <div className="footer-baseline"><span>© {new Date().getFullYear()} Связь</span><div><button onClick={() => open('privacy')}>О данных</button><button onClick={() => open('rules')}>Правила общения</button></div><span>Только 18+</span></div>
        </footer>

        {(modal === 'login' || modal === 'register') && <AuthModal mode={modal} setMode={open} onClose={close} run={run} busy={busy} error={error} onDone={enterCabinet}/>}
        {modal === 'privacy' && <Modal label="О данных" onClose={close}><div className="eyebrow">ПРИВАТНОСТЬ</div><h2>Что остаётся<br/>в вашей анкете</h2><div className="landing-document"><p>Зарегистрированные пользователи видят имя, возраст, город, фотографии, интересы и описание. Гостям доступны только демонстрационные анкеты.</p><p>Почта и полная дата рождения не показываются другим пользователям. Пароль хранится в виде хеша. Чаты доступны только участникам взаимного совпадения.</p><p>В настройках можно скрыть анкету или удалить аккаунт. При удалении также удаляются загруженные фотографии, симпатии и связанные переписки.</p></div></Modal>}
        {modal === 'rules' && <Modal label="Правила общения" onClose={close}><div className="eyebrow">КОРОТКО О ГЛАВНОМ</div><h2>Знакомиться<br/>с уважением</h2><div className="landing-document"><p><strong>Только для взрослых.</strong> Пользоваться Связью можно с 18 лет. В анкете должны быть ваши фотографии и достоверный возраст.</p><p><strong>Без давления.</strong> Уважайте отказ. Угрозам, травле, спаму и попыткам обмана здесь не место.</p><p><strong>С заботой о себе.</strong> Не отправляйте деньги незнакомым людям. Для первой встречи выбирайте общественное место.</p><p>Пожаловаться или заблокировать человека можно через меню его анкеты. Жалоба сохраняется, а заблокированный пользователь теряет возможность писать вам.</p></div></Modal>}
        {busy && <div className="landing-loading" role="status" aria-label="Выполняется вход"><LoaderCircle className="spin" size={18}/></div>}
    </div>;
}
