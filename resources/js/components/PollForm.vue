<script setup>
  // Utilisation de la Composition API
  import { computed, ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  // CHANGEMENT :
  // Le formulaire peut maintenant recevoir un sondage existant.
  // Si poll vaut null → création
  // Si poll contient un objet → édition
  const props = defineProps({
    poll: { type: Object, default: null },
  });

  // CHANGEMENT :
  // On récupère aussi updatePoll pour pouvoir modifier un sondage.
  const { createPoll, updatePoll, loading, error, clearError } = usePollStore();

  // CHANGEMENT :
  // Permet de savoir si le formulaire est en mode édition.
  const isEditing = computed(() => props.poll !== null);

  // CHANGEMENT :
  // Les champs sont préremplis si on édite un sondage.
  const title = ref(props.poll?.title || '');
  const question = ref(props.poll?.question || '');
  const options = ref(
    props.poll?.options?.length
      ? props.poll.options.map(option => ({
          id: option.id,
          label: option.label,
        }))
      : [
          { label: '' },
          { label: '' },
        ]
  );

  // CHANGEMENT :
  // En création, un nouveau sondage est lancé par défaut.
  // En édition, on reprend explicitement la vraie valeur du sondage.
  // Cela garantit qu'un brouillon reste coché quand on ouvre le formulaire de modification.
  const isDraft = ref(props.poll ? Boolean(props.poll.is_draft) : false);
  const allowMultipleChoices = ref(props.poll ? Boolean(props.poll.allow_multiple_choices) : false);
  const resultsPublic = ref(props.poll ? Boolean(props.poll.results_public) : false);
  const duration = ref(props.poll?.duration || '');

  function addOption() {
    options.value.push({ label: '' });
  }

  function removeOption(index) {
    if (options.value.length <= 2) return;
    options.value.splice(index, 1);
  }

  async function submitForm() {
    clearError();

    const payload = {
      title: title.value || null,
      question: question.value,
      options: options.value.filter(option => option.label.trim() !== ''),
      is_draft: isDraft.value,
      allow_multiple_choices: allowMultipleChoices.value,
      allow_vote_change: false,
      results_public: resultsPublic.value,
      duration: duration.value ? Number(duration.value) : null,
    };

    // CHANGEMENT :
    // Si on édite, on appelle PATCH avec updatePoll.
    // Sinon, on appelle POST avec createPoll.
    const result = isEditing.value
      ? await updatePoll(props.poll.id, payload)
      : await createPoll(payload);

    if (result) {
      // CHANGEMENT :
      // Après modification ou création, on retourne au dashboard.
      window.location.href = '/polls/dashboard';
    }
  }
</script>

<template>
  <form class="poll-form" @submit.prevent="submitForm">
    <!-- CHANGEMENT :
         On enlève le titre interne "Créer/Modifier le sondage"
         pour éviter la répétition avec la page et alléger le formulaire. -->

    <p v-if="error" class="error">{{ error }}</p>

    <div>
      <label for="title">Titre</label>
      <input id="title" v-model="title" type="text">
    </div>

    <div>
      <label for="question">Question *</label>
      <input id="question" v-model="question" type="text" required>
    </div>

    <div>
      <label>Options *</label>

      <div
        v-for="(option, index) in options"
        :key="option.id || index"
        class="option-row"
      >
        <input
          v-model="option.label"
          type="text"
          :placeholder="'Option ' + (index + 1)"
          required
        >

        <!-- CHANGEMENT :
             Le bouton Retirer reçoit une classe dédiée
             pour avoir un style plus clair et cohérent. -->
        <button
          class="remove-button"
          type="button"
          @click="removeOption(index)"
          :disabled="options.length <= 2"
        >
          Retirer
        </button>
      </div>

      <!-- CHANGEMENT :
           Le bouton "Ajouter une option" devient une action secondaire
           plus visible et plus cohérente avec le reste de l'interface. -->
      <button class="add-button" type="button" @click="addOption">
        + Ajouter une option
      </button>
    </div>

    <div class="settings">
      <label>
        <input v-model="isDraft" type="checkbox">
        Brouillon
      </label>

      <label>
        <input v-model="allowMultipleChoices" type="checkbox">
        Choix multiple
      </label>

      <label>
        <input v-model="resultsPublic" type="checkbox">
        Résultats publics
      </label>
    </div>

    <!-- CHANGEMENT :
         La durée devient une liste déroulante pour éviter que l’utilisateur doive entrer des secondes. -->
    <div>
      <label for="duration">Durée de disponibilité</label>

      <select id="duration" v-model="duration">
        <option value="">Aucune limite</option>
        <option value="86400">1 jour</option>
        <option value="259200">3 jours</option>
        <option value="604800">7 jours</option>
      </select>
    </div>

    <!-- CHANGEMENT :
         Le texte du bouton change selon le mode création / édition. -->
    <button class="submit-button" type="submit" :disabled="loading">
      <span v-if="loading">
        {{ isEditing ? 'Modification...' : 'Création...' }}
      </span>

      <span v-else>
        {{ isEditing ? 'Modifier le sondage' : 'Créer le sondage' }}
      </span>
    </button>
  </form>
</template>

<style scoped>
  /* CHANGEMENT :
     Formulaire centré et limité en largeur pour éviter qu’il prenne toute la page. */
  .poll-form {
    max-width: 700px;
    margin: 0 auto 2rem;
    padding: 1.5rem;
    border: 1px solid #ddd;
    border-radius: 0.75rem;
    background-color: white;
  }

  .poll-form div {
    margin-bottom: 1rem;
  }

  label {
    display: block;
    margin-bottom: 0.25rem;
    font-weight: bold;
  }

  input[type="text"],
  input[type="number"],
  select {
    width: 100%;
    padding: 0.65rem;
    border: 1px solid #ccc;
    border-radius: 0.35rem;
  }

  /* CHANGEMENT :
     Les options sont alignées proprement avec un espace plus régulier
     entre le champ texte et le bouton Retirer. */
  .option-row {
    display: flex;
    gap: 0.75rem;
    align-items: center;
  }

  /* CHANGEMENT :
     Le champ texte de l'option prend tout l'espace disponible. */
  .option-row input {
    flex: 1;
  }

  /* CHANGEMENT :
     Style spécifique du bouton Retirer.
     Le rouge indique une action destructive, mais reste moins agressif que le bouton Supprimer. */
  .remove-button {
    padding: 0.55rem 0.8rem;
    color: #dc2626;
    background-color: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 0.4rem;
    font-weight: 700;
    cursor: pointer;
  }

  .remove-button:hover:not(:disabled) {
    background-color: #fecaca;
  }

  .remove-button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }

  /* CHANGEMENT :
     Style spécifique du bouton Ajouter une option.
     C'est une action secondaire, donc on utilise un contour bleu au lieu d'un bouton plein. */
  .add-button {
    margin-top: 0.25rem;
    padding: 0.6rem 0.9rem;
    color: #2563eb;
    background-color: white;
    border: 1px dashed #2563eb;
    border-radius: 0.4rem;
    font-weight: 700;
    cursor: pointer;
  }

  .add-button:hover {
    background-color: #eff6ff;
  }

  .settings label {
    font-weight: normal;
  }

  button {
    border: none;
  }

  .error {
    padding: 0.75rem;
    color: #842029;
    background-color: #f8d7da;
    border-radius: 0.25rem;
  }

  .submit-button {
    display: inline-block;
    padding: 0.75rem 1rem;
    background-color: #2563eb;
    color: white;
    border: none;
    border-radius: 0.5rem;
    font-weight: 700;
    cursor: pointer;
  }

  .submit-button:hover {
    background-color: #1d4ed8;
  }

  .submit-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  /* CHANGEMENT :
     Adaptation mobile du formulaire.
     Sur téléphone, les options passent en colonne
     et les boutons prennent toute la largeur pour être plus faciles à utiliser. */
  @media (max-width: 700px) {
    .poll-form {
      margin: 0 1rem 2rem;
      padding: 1rem;
    }

    .option-row {
      flex-direction: column;
      align-items: stretch;
      gap: 0.5rem;
    }

    .remove-button,
    .add-button,
    .submit-button {
      width: 100%;
    }

    .settings {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
  }
</style>