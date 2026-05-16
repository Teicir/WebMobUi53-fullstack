<script setup>
import { computed, onMounted, ref } from 'vue';

// CHANGEMENT :
// Les données sont maintenant reçues depuis Blade via props.
const props = defineProps({
  poll: Object,
  totalVotes: Number,
  canShowResults: Boolean,
  voteUrl: String,
  pollApiUrl: String,
  csrfToken: String,
});

// CHANGEMENT :
// On garde une copie réactive du sondage
// pour mettre à jour les résultats sans recharger la page.
const currentPoll = ref(props.poll);

const message = ref('');
const messageType = ref('');

const selectedOptionIds = ref([]);

// CHANGEMENT :
// Vérifie si le sondage est fermé.
const isClosed = computed(() => {
  if (!currentPoll.value.ends_at) return false;

  return new Date() > new Date(currentPoll.value.ends_at);
});

// CHANGEMENT :
// Calcul dynamique du total des votes.
const computedTotalVotes = computed(() => {
  return currentPoll.value.options.reduce((total, option) => {
    return total + option.votes_count;
  }, 0);
});

// CHANGEMENT :
// Recharge les résultats depuis l'API.
async function fetchResults() {
  if (!props.canShowResults) return;

  try {
    const response = await fetch(props.pollApiUrl, {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) return;

    currentPoll.value = await response.json();
  } catch (error) {
    console.error(error);
  }
}

// CHANGEMENT :
// Envoi du vote via fetch API.
async function submitVote() {
  // CHANGEMENT :
  // Pour un sondage à choix multiple, selectedOptionIds est déjà un tableau.
  // Pour un sondage à choix unique, Vue renvoie une seule valeur avec les boutons radio.
  // On convertit donc toujours la sélection en tableau pour respecter ce que l'API attend.
  const optionIds = Array.isArray(selectedOptionIds.value)
    ? selectedOptionIds.value
    : [selectedOptionIds.value];

  if (optionIds.length === 0 || optionIds[0] === undefined || optionIds[0] === null) {
    message.value = 'Veuillez sélectionner une réponse.';
    messageType.value = 'error';
    return;
  }

  try {
    const response = await fetch(props.voteUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': props.csrfToken,
      },
      body: JSON.stringify({
        option_ids: optionIds,
      }),
    });

    if (!response.ok) {
      const errorData = await response.json();

      throw new Error(errorData.message);
    }

    message.value = 'Votre vote a bien été enregistré.';
    messageType.value = 'success';

    fetchResults();
  } catch (error) {
    if (error.message === 'You have already voted for this poll.') {
      message.value = 'Vous avez déjà voté à ce sondage.';
    } else if (error.message === 'This poll is still a draft.') {
      message.value = 'Ce sondage est encore en brouillon.';
    } else {
      message.value = 'Erreur lors de l’enregistrement du vote.';
    }

    messageType.value = 'error';
  }
}

// CHANGEMENT :
// Polling automatique des résultats toutes les 3 secondes.
onMounted(() => {
  if (props.canShowResults) {
    fetchResults();

    setInterval(fetchResults, 3000);
  }
});
</script>

<template>
  <main class="poll-page">
    <section class="poll-card">
      <h1>{{ currentPoll.title || 'Sondage' }}</h1>

      <p class="question">
        {{ currentPoll.question }}
      </p>

      <p v-if="isClosed" class="error-message">
        Ce sondage est terminé. Il n’est plus possible de voter.
      </p>

      <form v-else @submit.prevent="submitVote">
        <div class="options-list">
          <label
            v-for="option in currentPoll.options"
            :key="option.id"
            class="option-card"
          >
            <input
              v-if="currentPoll.allow_multiple_choices"
              v-model="selectedOptionIds"
              type="checkbox"
              :value="option.id"
            >

            <input
              v-else
              v-model="selectedOptionIds"
              type="radio"
              :value="option.id"
            >

            <span>{{ option.label }}</span>
          </label>
        </div>

        <button class="vote-button" type="submit">
          Voter
        </button>

        <p
          v-if="message"
          class="vote-message"
          :class="messageType === 'success'
            ? 'success-message'
            : 'error-message'"
        >
          {{ message }}
        </p>
      </form>
    </section>

    <section class="poll-card results-card">
      <h2>Résultats</h2>

      <template v-if="canShowResults">
        <p class="results-total">
          Total des votes : {{ computedTotalVotes }}
        </p>

        <div class="results-list">
          <div
            v-for="option in currentPoll.options"
            :key="option.id"
            class="result-item"
          >
            <div class="result-header">
              <span>{{ option.label }}</span>

              <span>
                {{ option.votes_count }} vote(s)
                —
                {{
                  computedTotalVotes > 0
                    ? Math.round((option.votes_count / computedTotalVotes) * 100)
                    : 0
                }}%
              </span>
            </div>

            <div class="result-bar">
              <div
                class="result-bar-fill"
                :style="{
                  width:
                    (
                      computedTotalVotes > 0
                        ? (option.votes_count / computedTotalVotes) * 100
                        : 0
                    ) + '%'
                }"
              ></div>
            </div>
          </div>
        </div>
      </template>

      <p v-else class="muted-message">
        Les résultats de ce sondage ne sont pas publics.
      </p>
    </section>
  </main>
</template>

<style scoped>
.poll-page {
  max-width: 700px;
  margin: 2rem auto;
  padding: 0 1rem;
}

.poll-card {
  padding: 1.5rem;
  background: white;
  border: 1px solid #ddd;
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
}

.question {
  font-size: 1.2rem;
  font-weight: bold;
  margin-bottom: 1.5rem;
}

.options-list,
.results-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.option-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  cursor: pointer;
}

.option-card:hover {
  background-color: #f8fafc;
}

.vote-button {
  padding: 0.75rem 1rem;
  color: white;
  background-color: #2563eb;
  border: none;
  border-radius: 0.5rem;
  font-weight: 700;
  cursor: pointer;
}

.vote-button:hover {
  background-color: #1d4ed8;
}

.vote-message {
  margin-top: 1rem;
  font-weight: 600;
}

.success-message {
  color: #166534;
}

.error-message {
  color: #b91c1c;
}

.muted-message,
.results-total {
  color: #64748b;
}

.result-item {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.result-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  font-weight: 600;
}

.result-bar {
  height: 0.75rem;
  overflow: hidden;
  background-color: #e2e8f0;
  border-radius: 999px;
}

.result-bar-fill {
  height: 100%;
  background-color: #2563eb;
  border-radius: 999px;
}

@media (max-width: 700px) {
  .poll-page {
    margin: 1rem auto;
    padding: 0 1rem;
  }

  .poll-card {
    padding: 1rem;
  }

  .vote-button {
    width: 100%;
  }

  .result-header {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>