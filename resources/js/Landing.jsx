import React, { useState } from 'react';
import { ArrowDown, ArrowUpRight, Plus, Minus, LoaderCircle } from 'lucide-react';
import { AuthModal, Brand, Flower, Modal } from './components/Shared';
import ZoomerHome from './components/ZoomerHome';
import '../css/zoomer.css';

const questions = [
    ['Тут обязательно влюбляться?', 'Нет. Выбирай отношения, дружбу или общение. Ищи человека, с которым хочется продолжить разговор, а дальше разберётесь.'],
    ['В личку кто угодно залетит?', 'Не-а. Сначала лайк с обеих сторон, потом чат. Без взаимной симпатии написать не получится.'],
    ['А если человек оказался редфлагом?', 'В меню анкеты есть блокировка и жалоба. Если хочется отдохнуть от знакомств — скрой свою анкету в настройках. Переписки сохранятся.'],
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

    return <div className="landing zoomer-landing">
        <a className="skip-to-content" href="#main-content">К содержимому</a>
        <header className="landing-header">
            <Brand/>
            <nav aria-label="О проекте"><a href="#how-it-works">Вайбы</a><a href="#questions">Вопросы</a></nav>
            {signedIn ? <span className="landing-session"><i/>Вы вошли</span> : <button className="landing-sign-in" onClick={() => open('login')}>Войти<ArrowUpRight size={16}/></button>}
        </header>

        <ZoomerHome signedIn={signedIn} onRegister={() => open('register')}/>

        <footer className="landing-footer" id="questions">
            <div className="footer-questions">
                <div><span className="editorial-label">КРАТКО, МЫ НЕ ДУШНИМ</span><h2>ЕСТЬ<br/><span>ВОПРОСИКИ?</span></h2><p>Мемы мемами. Чужие границы уважаем.</p></div>
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
