@props([
    'bodyClass' => 'min-h-screen bg-slate-50 text-slate-900 antialiased',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @isset($title)
        <title>{{ $title }} - Sondages</title>
    @else
        <title>Sondages</title>
    @endisset

    @vite(['resources/css/app.css'])
    @isset($scripts)
        {{ $scripts }}
    @endisset
</head>

<body {{ $attributes->class([$bodyClass]) }}>
    <!-- CHANGEMENT :
         Header commun responsive pour faciliter la navigation et les tests multi-utilisateurs. -->
    <header class="app-header">
        <nav class="app-nav">
            <!-- CHANGEMENT :
                 On remplace le nom de l'application de base par "Sondages"
                 pour que le header corresponde au projet actuel. -->
            <a href="/" class="app-logo">
                Sondages
            </a>

            <div class="app-nav-links">
                @auth
                    <a href="{{ route('polls.dashboard') }}" class="nav-link">
                        Dashboard
                    </a>

                    <!-- CHANGEMENT :
                         On réutilise le bouton profil déjà présent dans le layout principal.
                         Il pointe vers /my-profile et affiche soit la photo de profil,
                         soit l'icône SVG par défaut du projet. -->
                    <a href="{{ url('/my-profile') }}" class="profile-link" title="Mon profil">
                        <div class="profile-avatar">
                            @if (Auth::user()->profile_picture)
                                <img
                                    src="{{ asset('storage/' . Auth::user()->profile_picture) }}"
                                    alt="{{ Auth::user()->username }}"
                                    class="profile-image"
                                >
                            @else
                                <img
                                    src="/icons/profile.svg"
                                    alt="{{ Auth::user()->username }}"
                                    class="profile-image"
                                >
                            @endif
                        </div>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="nav-link">
                        Connexion
                    </a>
                @endauth
            </div>
        </nav>
    </header>

    {{ $slot }}

    <style>
        .app-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 2rem;
        }

        .app-nav {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .app-logo {
            font-weight: 800;
            font-size: 1.1rem;
            text-decoration: none;
            color: #0f172a;
            white-space: nowrap;
        }

        .app-nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-link {
            text-decoration: none;
            color: #334155;
            font-weight: 600;
        }

        .nav-link:hover {
            color: #0f172a;
        }

        /* CHANGEMENT :
           Lien vers le profil utilisateur.
           On garde le comportement du layout principal, mais avec un style adapté au header Vue. */
        .profile-link {
            display: block;
            text-decoration: none;
            transition: opacity 0.2s ease;
        }

        .profile-link:hover {
            opacity: 0.8;
        }

        /* CHANGEMENT :
           Avatar rond réutilisable pour la photo de profil ou l'icône par défaut. */
        .profile-avatar {
            height: 2rem;
            width: 2rem;
            border-radius: 999px;
            overflow: hidden;
            background: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* CHANGEMENT :
           Sur mobile, le header reste simple :
           nom de l'application à gauche, navigation/profil à droite.
           Les éléments peuvent passer à la ligne si l'espace est trop petit. */
        @media (max-width: 700px) {
            .app-nav {
                align-items: center;
                flex-wrap: wrap;
            }

            .app-logo {
                white-space: normal;
            }

            .app-nav-links {
                margin-left: auto;
            }
        }
    </style>
</body>

</html>