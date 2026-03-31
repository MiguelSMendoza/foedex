import { useEffect, useMemo, useRef, useState, useTransition } from 'react';
import { Link, NavLink, Route, Routes, useLocation, useNavigate, useParams } from 'react-router-dom';

const HOME_BATCH_SIZE = 6;

async function apiFetch(path, options = {}) {
  const isFormData = typeof FormData !== 'undefined' && options.body instanceof FormData;
  const response = await fetch(path, {
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      ...(!isFormData && options.body ? { 'Content-Type': 'application/json' } : {}),
      ...options.headers,
    },
    ...options,
  });

  const contentType = response.headers.get('content-type') ?? '';
  const payload = contentType.includes('application/json') ? await response.json() : null;
  const text = payload === null ? await response.text() : null;

  if (!response.ok) {
    const message = payload?.message ?? payload?.errors?.[0] ?? 'La petición ha fallado.';
    throw new Error(message);
  }

  if (payload === null) {
    throw new Error(
      text && text.trim() !== ''
        ? 'El servidor ha respondido con HTML inesperado. Revisa si tu sesión sigue activa.'
        : 'El servidor ha respondido con un formato inesperado.',
    );
  }

  return payload;
}

function App() {
  const [session, setSession] = useState({ loading: true, user: null });

  useEffect(() => {
    let active = true;

    apiFetch('/api/session')
      .then((payload) => {
        if (active) {
          setSession({ loading: false, user: payload.user });
        }
      })
      .catch(() => {
        if (active) {
          setSession({ loading: false, user: null });
        }
      });

    return () => {
      active = false;
    };
  }, []);

  return (
    <div className="app-shell">
      <Topbar session={session} setSession={setSession} />
      <Routes>
        <Route path="/" element={<HomePage session={session} />} />
        <Route path="/pages/:slug" element={<PageShow session={session} />} />
        <Route path="/categories" element={<CategoryIndex />} />
        <Route path="/categories/:slug" element={<CategoryShow />} />
        <Route path="/login" element={<LoginPage setSession={setSession} />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="/profile" element={<ProfilePage session={session} setSession={setSession} />} />
        <Route path="/editor/new" element={<EditorPage session={session} mode="create" />} />
        <Route path="/editor/:slug/edit" element={<EditorPage session={session} mode="edit" />} />
      </Routes>
    </div>
  );
}

function Topbar({ session, setSession }) {
  const navigate = useNavigate();
  const [isPending, startTransition] = useTransition();

  async function logout() {
    await apiFetch('/api/logout', { method: 'POST' });
    setSession({ loading: false, user: null });
    startTransition(() => navigate('/'));
  }

  return (
    <header className="topbar">
      <div className="layout topbar-inner">
        <Link className="brand" to="/">Foedex</Link>
        <nav className="topnav">
          <NavLink to="/">Inicio</NavLink>
          <NavLink to="/categories">Categorias</NavLink>
          <NavLink to="/editor/new">Nueva pagina</NavLink>
          {session.user ? (
            <>
              <NavLink to="/profile">{session.user.displayName}</NavLink>
              <button type="button" className="ghost-button" disabled={isPending} onClick={logout}>Salir</button>
            </>
          ) : (
            <>
              <NavLink to="/login">Entrar</NavLink>
              <NavLink to="/register">Registro</NavLink>
            </>
          )}
        </nav>
      </div>
    </header>
  );
}

function HomePage({ session }) {
  const location = useLocation();
  const searchParams = useMemo(() => new URLSearchParams(location.search), [location.search]);
  const [query, setQuery] = useState(searchParams.get('q') ?? '');
  const [state, setState] = useState({
    loading: true,
    loadingMore: false,
    pages: [],
    categories: [],
    error: null,
    hasMore: true,
    nextOffset: 0,
  });
  const sentinelRef = useRef(null);
  const requestStateRef = useRef({
    loading: true,
    loadingMore: false,
    hasMore: true,
    nextOffset: 0,
  });

  useEffect(() => {
    requestStateRef.current = {
      loading: state.loading,
      loadingMore: state.loadingMore,
      hasMore: state.hasMore,
      nextOffset: state.nextOffset,
    };
  }, [state.loading, state.loadingMore, state.hasMore, state.nextOffset]);

  const navigate = useNavigate();
  const [deletingSlug, setDeletingSlug] = useState(null);

  async function loadPages(offset, replace = false) {
    const currentQuery = searchParams.get('q') ?? '';

    setState((previous) => ({
      ...previous,
      loading: replace,
      loadingMore: !replace,
      error: null,
    }));

    try {
      const payload = await apiFetch(
        `/api/home?limit=${HOME_BATCH_SIZE}&offset=${offset}${currentQuery ? `&q=${encodeURIComponent(currentQuery)}` : ''}`,
      );

      setState((previous) => ({
        loading: false,
        loadingMore: false,
        error: null,
        categories: payload.categories,
        pages: replace
          ? payload.pages
          : [...previous.pages, ...payload.pages.filter((candidate) => !previous.pages.some((page) => page.slug === candidate.slug))],
        hasMore: payload.hasMore,
        nextOffset: payload.nextOffset,
      }));
    } catch (error) {
      setState((previous) => ({
        ...previous,
        loading: false,
        loadingMore: false,
        error: error.message,
      }));
    }
  }

  useEffect(() => {
    const currentQuery = searchParams.get('q') ?? '';

    setQuery(currentQuery);
    void loadPages(0, true);
  }, [searchParams]);

  useEffect(() => {
    const sentinel = sentinelRef.current;

    if (!sentinel) {
      return undefined;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        const requestState = requestStateRef.current;

        if (!entry?.isIntersecting || requestState.loading || requestState.loadingMore || !requestState.hasMore) {
          return;
        }

        void loadPages(requestState.nextOffset, false);
      },
      { rootMargin: '260px' },
    );

    observer.observe(sentinel);

    return () => observer.disconnect();
  }, [searchParams]);

  function handleSearch(event) {
    event.preventDefault();
    const trimmed = query.trim();
    navigate(trimmed ? `/?q=${encodeURIComponent(trimmed)}` : '/');
  }

  async function handleDelete(page) {
    if (!session.user || page.createdBy.id !== session.user.id) {
      return;
    }

    const confirmed = window.confirm(`Vas a archivar "${page.title}". Esta accion lo quitara de la portada y listados publicos.`);

    if (!confirmed) {
      return;
    }

    setDeletingSlug(page.slug);

    try {
      await apiFetch(`/api/pages/${page.slug}`, { method: 'DELETE' });
      setState((current) => ({
        ...current,
        pages: current.pages.filter((candidate) => candidate.slug !== page.slug),
        nextOffset: Math.max(0, current.nextOffset - 1),
      }));
    } catch (error) {
      setState((current) => ({
        ...current,
        error: error.message,
      }));
    } finally {
      setDeletingSlug(null);
    }
  }

  return (
    <main className="layout home-shell">
      <section className="hero-card home-search-panel">
        <div className="home-search-head">
          <div>
            <p className="eyebrow">Foedex</p>
            <h1>Busca o captura conocimiento</h1>
          </div>
          <p className="muted">
            Primero encuentra lo que ya existe. Si no esta, lo capturas en segundos.
          </p>
        </div>
        <form className="search-form search-form-wide" onSubmit={handleSearch}>
          <input
            type="search"
            placeholder="Busca por titulo o contenido"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
          />
          <button type="submit">Buscar</button>
        </form>
      </section>

      {session.user ? (
        <QuickCreatePanel
          onCreated={(page) => {
            setState((current) => ({
              ...current,
              pages: [page, ...current.pages.filter((candidate) => candidate.slug !== page.slug)],
              nextOffset: current.nextOffset + 1,
            }));
          }}
        />
      ) : (
        <section className="side-panel quick-panel quick-panel-locked">
          <div className="section-heading">
            <h2>Creacion rapida</h2>
          </div>
          <p className="muted">
            Inicia sesion para pegar enlaces, arrastrar imagenes o subir ficheros y convertirlos en paginas sin recargar.
          </p>
          <div className="quick-actions">
            <Link className="primary-link" to="/login">Entrar</Link>
            <Link className="ghost-button" to="/register">Crear cuenta</Link>
          </div>
        </section>
      )}

      <aside className="side-panel home-context">
        <div className="section-heading">
          <h2>Categorias</h2>
          <Link className="inline-action" to="/categories">Ver todas</Link>
        </div>
        <div className="tag-cloud">
          {state.categories.slice(0, 12).map((category) => (
            <Link key={category.slug} className="tag" to={`/categories/${category.slug}`}>{category.name}</Link>
          ))}
        </div>
      </aside>

      <section className="content-panel home-feed">
        <div className="section-heading">
          <h2>{searchParams.get('q') ? 'Resultados completos' : 'Flujo de conocimiento reciente'}</h2>
        </div>
        {state.loading && <p>Cargando...</p>}
        {state.error && <p className="error-copy">{state.error}</p>}
        <div className="feed-list">
          {state.pages.map((page) => (
            <article key={page.slug} className="content-panel feed-card">
              <div className="page-card-head">
                <div>
                  <p className="eyebrow small">{page.slug}</p>
                  <h3><Link to={`/pages/${page.slug}`}>{page.title}</Link></h3>
                </div>
                <div className="page-card-actions">
                  <span className="meta-chip">{new Date(page.updatedAt).toLocaleDateString('es-ES')}</span>
                  {session.user && (
                    <Link className="inline-action" to={`/editor/${page.slug}/edit`}>Editar</Link>
                  )}
                  {session.user && page.createdBy.id === session.user.id && (
                    <button
                      type="button"
                      className="danger-button inline-action-button"
                      disabled={deletingSlug === page.slug}
                      onClick={() => void handleDelete(page)}
                    >
                      {deletingSlug === page.slug ? 'Borrando...' : 'Borrar'}
                    </button>
                  )}
                </div>
              </div>
              <div className="meta-row">
                <span>Creada por {page.createdBy.displayName}</span>
                <span>Ultima edicion de {page.lastEditedBy.displayName}</span>
              </div>
              <div className="tag-cloud">
                {page.categories.map((category) => (
                  <Link key={category.slug} className="tag" to={`/categories/${category.slug}`}>{category.name}</Link>
                ))}
              </div>
              <div className="html-copy article-copy" dangerouslySetInnerHTML={{ __html: page.html }} />
            </article>
          ))}
        </div>
        {!state.loading && state.loadingMore && <p className="muted">Cargando mas paginas...</p>}
        {!state.loading && !state.hasMore && state.pages.length > 0 && <p className="muted">No hay mas paginas por cargar.</p>}
        <div ref={sentinelRef} className="feed-sentinel" aria-hidden="true" />
      </section>
    </main>
  );
}

function PageShow({ session }) {
  const { slug } = useParams();
  const [state, setState] = useState({ loading: true, page: null, history: [], error: null });
  const [modalMedia, setModalMedia] = useState(null);

  useEffect(() => {
    let active = true;

    apiFetch(`/api/pages/${slug}`)
      .then((payload) => {
        if (active) {
          if (payload.redirectTo) {
            window.location.assign(payload.redirectTo);
            return;
          }

          setState({ loading: false, page: payload.page, history: payload.history, error: null });
        }
      })
      .catch((error) => {
        if (active) {
          setState({ loading: false, page: null, history: [], error: error.message });
        }
      });

    return () => {
      active = false;
    };
  }, [slug]);

  async function restoreRevision(revisionNumber) {
    const payload = await apiFetch(`/api/pages/${slug}/revisions/${revisionNumber}/restore`, { method: 'POST' });
    setState((previous) => ({
      ...previous,
      page: payload.page,
    }));
  }

  if (state.loading) {
    return <ScreenMessage text="Cargando pagina..." />;
  }

  if (state.error || !state.page) {
    return <ScreenMessage text={state.error ?? 'No se ha podido cargar la pagina.'} error />;
  }

  return (
    <main className="layout detail-grid">
      <article className="content-panel article">
        <p className="eyebrow">{state.page.slug}</p>
        <div className="article-headline">
          <div>
            <h1>{state.page.title}</h1>
            {state.page.excerpt && <p className="lead">{state.page.excerpt}</p>}
          </div>
          {session.user && <Link className="primary-link" to={`/editor/${state.page.slug}/edit`}>Editar</Link>}
        </div>
        <div className="meta-row">
          <span>Creada por {state.page.createdBy.displayName}</span>
          <span>Ultima edicion de {state.page.lastEditedBy.displayName}</span>
        </div>
        <div className="tag-cloud roomy">
          {state.page.categories.map((category) => (
            <Link key={category.slug} className="tag" to={`/categories/${category.slug}`}>{category.name}</Link>
          ))}
        </div>
        <PageMediaPreview page={state.page} onOpenMedia={setModalMedia} expanded />
        <div className="html-copy article-copy" dangerouslySetInnerHTML={{ __html: state.page.html }} />
      </article>

      <aside className="side-panel">
        <h2>Historial reciente</h2>
        <div className="history-list">
          {state.history.map((revision) => (
            <article key={revision.id} className="history-item">
              <strong>Revision #{revision.revisionNumber}</strong>
              <span>{revision.author.displayName}</span>
              <span>{new Date(revision.createdAt).toLocaleString('es-ES')}</span>
              {revision.changeSummary && <p>{revision.changeSummary}</p>}
              {session.user && (
                <button type="button" onClick={() => restoreRevision(revision.revisionNumber)}>
                  Restaurar
                </button>
              )}
            </article>
          ))}
        </div>
      </aside>
      <ImageModal media={modalMedia} onClose={() => setModalMedia(null)} />
    </main>
  );
}

function CategoryIndex() {
  const [state, setState] = useState({ loading: true, categories: [], error: null });

  useEffect(() => {
    apiFetch('/api/categories')
      .then((payload) => setState({ loading: false, categories: payload.categories, error: null }))
      .catch((error) => setState({ loading: false, categories: [], error: error.message }));
  }, []);

  if (state.loading) {
    return <ScreenMessage text="Cargando categorias..." />;
  }

  return (
    <main className="layout content-panel">
      <h1>Categorias</h1>
      <div className="tag-cloud roomy">
        {state.categories.map((category) => (
          <Link key={category.slug} className="tag tag-large" to={`/categories/${category.slug}`}>
            {category.name}
          </Link>
        ))}
      </div>
    </main>
  );
}

function CategoryShow() {
  const { slug } = useParams();
  const [state, setState] = useState({ loading: true, category: null, pages: [], error: null });

  useEffect(() => {
    apiFetch(`/api/categories/${slug}`)
      .then((payload) => setState({ loading: false, category: payload.category, pages: payload.pages, error: null }))
      .catch((error) => setState({ loading: false, category: null, pages: [], error: error.message }));
  }, [slug]);

  if (state.loading) {
    return <ScreenMessage text="Cargando categoria..." />;
  }

  if (state.error || !state.category) {
    return <ScreenMessage text={state.error ?? 'Categoria no encontrada.'} error />;
  }

  return (
    <main className="layout detail-grid">
      <section className="content-panel">
        <p className="eyebrow">{state.category.slug}</p>
        <h1>{state.category.name}</h1>
        {state.category.description && <p className="lead">{state.category.description}</p>}
        <div className="page-list">
          {state.pages.map((page) => (
            <article key={page.slug} className="page-card">
              <h3><Link to={`/pages/${page.slug}`}>{page.title}</Link></h3>
              <div className="html-copy compact" dangerouslySetInnerHTML={{ __html: page.excerptHtml }} />
            </article>
          ))}
        </div>
      </section>
    </main>
  );
}

function LoginPage({ setSession }) {
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
  const [error, setError] = useState(null);
  const [isSaving, setIsSaving] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError(null);
    setIsSaving(true);

    try {
      await apiFetch('/api/login', {
        method: 'POST',
        body: JSON.stringify(form),
      });

      const payload = await apiFetch('/api/session');
      setSession({ loading: false, user: payload.user });
      navigate('/');
    } catch (submissionError) {
      setError(submissionError.message);
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <AuthLayout title="Entrar" subtitle="La sesion del backend la reutiliza el frontend React.">
      <form className="stack-form" onSubmit={handleSubmit}>
        <label>
          Email
          <input type="email" value={form.email} onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} />
        </label>
        <label>
          Contrasena
          <input type="password" value={form.password} onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))} />
        </label>
        {error && <p className="error-copy">{error}</p>}
        <button type="submit" disabled={isSaving}>{isSaving ? 'Entrando...' : 'Entrar'}</button>
      </form>
    </AuthLayout>
  );
}

function RegisterPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ displayName: '', email: '', password: '' });
  const [error, setError] = useState(null);
  const [message, setMessage] = useState(null);

  async function handleSubmit(event) {
    event.preventDefault();
    setError(null);

    try {
      const payload = await apiFetch('/api/register', {
        method: 'POST',
        body: JSON.stringify(form),
      });

      setMessage(payload.message);
      window.setTimeout(() => navigate('/login'), 800);
    } catch (submissionError) {
      setError(submissionError.message);
    }
  }

  return (
    <AuthLayout title="Registro" subtitle="Crea una cuenta para editar y restaurar contenido.">
      <form className="stack-form" onSubmit={handleSubmit}>
        <label>
          Nombre visible
          <input value={form.displayName} onChange={(event) => setForm((current) => ({ ...current, displayName: event.target.value }))} />
        </label>
        <label>
          Email
          <input type="email" value={form.email} onChange={(event) => setForm((current) => ({ ...current, email: event.target.value }))} />
        </label>
        <label>
          Contrasena
          <input type="password" value={form.password} onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))} />
        </label>
        {error && <p className="error-copy">{error}</p>}
        {message && <p className="success-copy">{message}</p>}
        <button type="submit">Crear cuenta</button>
      </form>
    </AuthLayout>
  );
}

function ProfilePage({ session, setSession }) {
  const navigate = useNavigate();
  const [form, setForm] = useState({ displayName: '', bio: '' });
  const [status, setStatus] = useState(null);

  useEffect(() => {
    if (!session.loading && !session.user) {
      navigate('/login');
      return;
    }

    if (session.user) {
      setForm({ displayName: session.user.displayName, bio: session.user.bio ?? '' });
    }
  }, [navigate, session]);

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus(null);

    try {
      const payload = await apiFetch('/api/profile', {
        method: 'PATCH',
        body: JSON.stringify(form),
      });

      setSession({ loading: false, user: payload.user });
      setStatus('Perfil actualizado.');
    } catch (error) {
      setStatus(error.message);
    }
  }

  if (session.loading) {
    return <ScreenMessage text="Cargando perfil..." />;
  }

  if (!session.user) {
    return null;
  }

  return (
    <AuthLayout title="Perfil" subtitle="Ajusta tu nombre visible y una bio corta.">
      <form className="stack-form" onSubmit={handleSubmit}>
        <label>
          Nombre visible
          <input value={form.displayName} onChange={(event) => setForm((current) => ({ ...current, displayName: event.target.value }))} />
        </label>
        <label>
          Bio
          <textarea rows="5" value={form.bio} onChange={(event) => setForm((current) => ({ ...current, bio: event.target.value }))} />
        </label>
        {status && <p className="success-copy">{status}</p>}
        <button type="submit">Guardar perfil</button>
      </form>
    </AuthLayout>
  );
}

function EditorPage({ session, mode }) {
  const navigate = useNavigate();
  const { slug } = useParams();
  const [categories, setCategories] = useState([]);
  const [form, setForm] = useState({
    title: '',
    slug: '',
    excerpt: '',
    markdown: '',
    changeSummary: '',
    newCategories: '',
  });
  const [selectedCategories, setSelectedCategories] = useState([]);
  const [status, setStatus] = useState({ loading: mode === 'edit', error: null, success: null });

  useEffect(() => {
    if (!session.loading && !session.user) {
      navigate('/login');
    }
  }, [navigate, session]);

  useEffect(() => {
    apiFetch('/api/categories')
      .then((payload) => setCategories(payload.categories))
      .catch(() => setCategories([]));
  }, []);

  useEffect(() => {
    if (mode !== 'edit' || !slug) {
      return;
    }

    apiFetch(`/api/pages/${slug}/editor`)
      .then((payload) => {
        setForm({
          title: payload.editor.title,
          slug: payload.editor.slug,
          excerpt: payload.editor.excerpt ?? '',
          markdown: payload.editor.markdown,
          changeSummary: '',
          newCategories: '',
        });
        setSelectedCategories(payload.editor.categories);
        setStatus({ loading: false, error: null, success: null });
      })
      .catch((error) => setStatus({ loading: false, error: error.message, success: null }));
  }, [mode, slug]);

  async function handleSubmit(event) {
    event.preventDefault();
    setStatus((current) => ({ ...current, error: null, success: null }));

    try {
      const payload = await apiFetch(mode === 'edit' ? `/api/pages/${slug}` : '/api/pages', {
        method: mode === 'edit' ? 'PUT' : 'POST',
        body: JSON.stringify({
          ...form,
          categories: selectedCategories,
        }),
      });

      setStatus({ loading: false, error: null, success: payload.message });
      navigate(`/pages/${payload.page.slug}`);
    } catch (error) {
      setStatus({ loading: false, error: error.message, success: null });
    }
  }

  if (session.loading || status.loading) {
    return <ScreenMessage text="Cargando editor..." />;
  }

  if (!session.user) {
    return null;
  }

  return (
    <main className="layout editor-grid">
      <section className="content-panel">
        <p className="eyebrow">{mode === 'edit' ? 'Editor de pagina' : 'Nueva pagina'}</p>
        <h1>{mode === 'edit' ? 'Editar con Markdown' : 'Crear con Markdown'}</h1>
        <p className="lead">
          Aqui si se ve y se edita el Markdown. Fuera del editor, la web publica siempre HTML interpretado.
        </p>
        <form className="stack-form" onSubmit={handleSubmit}>
          <label>
            Titulo
            <input value={form.title} onChange={(event) => setForm((current) => ({ ...current, title: event.target.value }))} />
          </label>
          <label>
            Slug
            <input value={form.slug} onChange={(event) => setForm((current) => ({ ...current, slug: event.target.value }))} />
          </label>
          <label>
            Extracto
            <textarea rows="3" value={form.excerpt} onChange={(event) => setForm((current) => ({ ...current, excerpt: event.target.value }))} />
          </label>
          <label>
            Categorias existentes
            <select
              multiple
              value={selectedCategories}
              onChange={(event) => {
                const values = Array.from(event.target.selectedOptions, (option) => option.value);
                setSelectedCategories(values);
              }}
            >
              {categories.map((category) => (
                <option key={category.slug} value={category.slug}>{category.name}</option>
              ))}
            </select>
          </label>
          <label>
            Nuevas categorias
            <input value={form.newCategories} onChange={(event) => setForm((current) => ({ ...current, newCategories: event.target.value }))} />
          </label>
          <label>
            Resumen del cambio
            <input value={form.changeSummary} onChange={(event) => setForm((current) => ({ ...current, changeSummary: event.target.value }))} />
          </label>
          <label>
            Markdown
            <textarea rows="18" value={form.markdown} onChange={(event) => setForm((current) => ({ ...current, markdown: event.target.value }))} />
          </label>
          {status.error && <p className="error-copy">{status.error}</p>}
          {status.success && <p className="success-copy">{status.success}</p>}
          <button type="submit">{mode === 'edit' ? 'Guardar cambios' : 'Crear pagina'}</button>
        </form>
      </section>

      <aside className="side-panel">
        <h2>Guia rapida</h2>
        <pre className="code-block"># Titulo
## Subtitulo
- lista
[enlace](https://example.com)
```php
echo 'hola';
```</pre>
      </aside>
    </main>
  );
}

function AuthLayout({ title, subtitle, children }) {
  return (
    <main className="layout auth-layout">
      <section className="content-panel auth-panel">
        <p className="eyebrow">Foedex</p>
        <h1>{title}</h1>
        <p className="lead">{subtitle}</p>
        {children}
      </section>
    </main>
  );
}

function ScreenMessage({ text, error = false }) {
  return (
    <main className="layout auth-layout">
      <section className="content-panel auth-panel">
        <p className={error ? 'error-copy' : 'muted'}>{text}</p>
      </section>
    </main>
  );
}

function QuickCreatePanel({ onCreated }) {
  const [input, setInput] = useState('');
  const [status, setStatus] = useState({ loading: false, message: null, error: null });
  const [dragActive, setDragActive] = useState(false);

  async function submitText(event) {
    event.preventDefault();

    if (!input.trim()) {
      return;
    }

    setStatus({ loading: true, message: null, error: null });

    try {
      const payload = await apiFetch('/api/quick-create/text', {
        method: 'POST',
        body: JSON.stringify({ input }),
      });

      setInput('');
      setStatus({ loading: false, message: payload.message, error: null });
      onCreated(payload.page);
    } catch (error) {
      setStatus({ loading: false, message: null, error: error.message });
    }
  }

  async function submitFile(file) {
    if (!file) {
      return;
    }

    const formData = new FormData();
    formData.append('file', file);
    setStatus({ loading: true, message: null, error: null });

    try {
      const payload = await apiFetch('/api/quick-create/upload', {
        method: 'POST',
        body: formData,
      });

      setStatus({ loading: false, message: payload.message, error: null });
      onCreated(payload.page);
    } catch (error) {
      setStatus({ loading: false, message: null, error: error.message });
    }
  }

  function handleDrop(event) {
    event.preventDefault();
    setDragActive(false);
    const file = event.dataTransfer.files?.[0];

    if (file) {
      void submitFile(file);
    }
  }

  function handlePaste(event) {
    const items = Array.from(event.clipboardData?.items ?? []);
    const fileItem = items.find((item) => item.kind === 'file');

    if (fileItem) {
      const file = fileItem.getAsFile();

      if (file) {
        event.preventDefault();
        void submitFile(file);
      }
    }
  }

  return (
    <section
      className={`hero-card quick-panel quick-panel-primary${dragActive ? ' drag-active' : ''}`}
      onDragOver={(event) => {
        event.preventDefault();
        setDragActive(true);
      }}
      onDragLeave={() => setDragActive(false)}
      onDrop={handleDrop}
    >
      <div className="section-heading">
        <h2>Creacion rapida</h2>
      </div>
      <p className="muted">
        Pega un enlace, una nota, una imagen o un fichero y deja que Foedex cree la pagina por ti sin cambiar de pantalla.
      </p>
      <form className="stack-form" onSubmit={submitText}>
        <label>
          Captura rapida
          <textarea
            rows="8"
            placeholder="Pega aqui un enlace, texto o usa arrastrar y soltar"
            value={input}
            onChange={(event) => setInput(event.target.value)}
            onPaste={handlePaste}
          />
        </label>
        <div className="quick-actions">
          <button type="submit" disabled={status.loading}>{status.loading ? 'Creando...' : 'Crear pagina'}</button>
          <label className="ghost-button file-picker">
            Subir fichero
            <input
              type="file"
              hidden
              onChange={(event) => {
                const file = event.target.files?.[0];
                if (file) {
                  void submitFile(file);
                }
                event.target.value = '';
              }}
            />
          </label>
        </div>
        {status.message && <p className="success-copy">{status.message}</p>}
        {status.error && <p className="error-copy">{status.error}</p>}
      </form>
    </section>
  );
}

function PageMediaPreview({ page, onOpenMedia, expanded = false }) {
  const primaryImage = page.media.find((asset) => asset.kind === 'image' && asset.thumbnailUrl);
  const primaryFile = !primaryImage ? page.media[0] : null;

  if (!primaryImage && !primaryFile) {
    return null;
  }

  if (primaryImage) {
    return (
      <div className={`media-preview${expanded ? ' expanded' : ''}`}>
        <button type="button" className="thumbnail-button" onClick={() => onOpenMedia(primaryImage)}>
          <img src={primaryImage.thumbnailUrl} alt={primaryImage.originalFilename} />
        </button>
      </div>
    );
  }

  return (
    <div className="file-preview">
      <a className="tag" href={primaryFile.url} target="_blank" rel="noreferrer">
        Descargar {primaryFile.originalFilename}
      </a>
    </div>
  );
}

function ImageModal({ media, onClose }) {
  if (!media) {
    return null;
  }

  return (
    <div className="modal-backdrop" onClick={onClose}>
      <div className="modal-panel" onClick={(event) => event.stopPropagation()}>
        <div className="section-heading">
          <h2>{media.originalFilename}</h2>
          <button type="button" className="ghost-button" onClick={onClose}>Cerrar</button>
        </div>
        <img className="modal-image" src={media.url} alt={media.originalFilename} />
        <a className="primary-link" href={media.url} target="_blank" rel="noreferrer" download>
          Descargar imagen
        </a>
      </div>
    </div>
  );
}

export default App;
