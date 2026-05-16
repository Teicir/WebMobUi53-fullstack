import { createApp } from 'vue';
import PollPublic from './components/PollPublic.vue';

// CHANGEMENT :
// Ce fichier est le point d'entrée Vue de la page publique d'un sondage.
// Il récupère les données envoyées par Blade via data-props,
// puis monte le composant PollPublic.
const appElement = document.querySelector('#poll-public-app');

if (appElement) {
  const props = JSON.parse(appElement.dataset.props);

  createApp(PollPublic, props).mount('#poll-public-app');
}