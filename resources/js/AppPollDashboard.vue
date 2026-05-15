<script setup>
  import PollForm from './components/PollForm.vue';
  import PollTable from './components/PollTable.vue';
  import { usePollStore } from '@/stores/usePollStore';

  const props = defineProps({
    polls: { type: Array, default: () => [] },
    mode: { type: String, default: 'index' },
    pollToEdit: { type: Object, default: null },
    loginUrl: { type: String, default: null },
    username: { type: String, default: null },
  });

  const { setPolls } = usePollStore();
  setPolls(props.polls);
</script>

<template>
  <!-- CHANGEMENT :
       Conteneur commun pour les boutons de navigation.
       Il a la même largeur que le tableau pour garder un alignement propre. -->
  <div class="dashboard-actions">
    <!-- CHANGEMENT :
         Sur le dashboard principal, le bouton de création est aligné à droite. -->
    <a
      v-if="mode === 'index'"
      class="create-button"
      href="/polls/dashboard/create"
    >
       Créer un sondage
    </a>

    <!-- CHANGEMENT :
         Sur les pages création/édition, le bouton retour reste aligné à gauche. -->
    <a
      v-else
      class="back-button"
      href="/polls/dashboard"
    >
      ← Retour au dashboard
    </a>
  </div>

  <PollForm
    v-if="mode === 'create' || mode === 'edit'"
    :poll="pollToEdit"
  />

  <PollTable v-else />
</template>

<style scoped>
  /* CHANGEMENT :
     Même largeur que PollTable pour aligner la navigation avec le contenu.
     On ne met pas justify-content: flex-end ici, sinon tous les liens partent à droite. */
  .dashboard-actions {
    max-width: 1100px;
    margin: 1rem auto;
    display: flex;
  }

  /* CHANGEMENT :
     Le bouton créer part seul à droite grâce à margin-left: auto. */
  .create-button {
    margin-left: auto;
    display: inline-block;
    padding: 0.75rem 1rem;
    color: white;
    background-color: #2563eb;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 700;
  }

  .create-button:hover {
    background-color: #1d4ed8;
  }

  /* CHANGEMENT :
     Le bouton retour reste naturellement à gauche.
     Il est blanc pour être moins fort visuellement que le bouton principal. */
  .back-button {
    display: inline-block;
    padding: 0.6rem 0.9rem;
    color: #334155;
    background-color: white;
    border: 1px solid #cbd5e1;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 600;
  }

  .back-button:hover {
    background-color: #f8fafc;
    border-color: #94a3b8;
  }
</style>