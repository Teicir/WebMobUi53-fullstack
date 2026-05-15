import { ref, shallowRef } from 'vue';
import { useFetchApi } from '@/composables/useFetchApi';

const polls = ref([]);
const error = shallowRef(null);
const loading = ref(false);

export function usePollStore() {
  const { fetchApi } = useFetchApi();

  function setPolls(data) {
    polls.value = data;
  }

  function clearError() {
    error.value = null;
  }

  // CHANGEMENT :
  // Fonction centralisée pour transformer les erreurs Laravel
  // en messages plus compréhensibles pour l'utilisateur.
  function getPollErrorMessage(err, fallbackMessage) {
    const message = err?.data?.message;
  
    // CHANGEMENT :
    // Message personnalisé pour les doublons d'options.
    if (
      message?.includes('valeur en double') ||
      message?.includes('duplicate')
    ) {
      return 'Les options du sondage doivent être différentes.';
    }
  
    return message || fallbackMessage;
  }

  async function createPoll(payload) {
    loading.value = true;
    error.value = null;

    try {
      const result = await fetchApi({
        url: 'polls',
        method: 'POST',
        data: payload,
      });

      polls.value = [result, ...polls.value];

      return result;
    } catch (err) {
      error.value = getPollErrorMessage(
        err,
        'Erreur avec le backend. Le sondage n’a pas pu être créé.'
      );

      return null;
    } finally {
      loading.value = false;
    }
  }

  async function updatePoll(id, payload) {
    loading.value = true;
    error.value = null;

    try {
      const result = await fetchApi({
        url: 'polls/' + id,
        method: 'PATCH',
        data: payload,
      });

      polls.value = polls.value.map(p => p.id === id ? result : p);

      return result;
    } catch (err) {
      error.value = getPollErrorMessage(
        err,
        'Erreur avec le backend. Le sondage n’a pas pu être modifié.'
      );

      return null;
    } finally {
      loading.value = false;
    }
  }

  async function deletePoll(id) {
    loading.value = true;
    error.value = null;

    try {
      await fetchApi({
        url: 'polls/' + id,
        method: 'DELETE',
      });

      polls.value = polls.value.filter(p => p.id !== id);
    } catch (err) {
      error.value = getPollErrorMessage(
        err,
        'Erreur avec le backend. Le sondage n’a pas pu être supprimé.'
      );
    } finally {
      loading.value = false;
    }
  }

  return {
    polls,
    error,
    loading,
    setPolls,
    clearError,
    createPoll,
    updatePoll,
    deletePoll,
  };
}