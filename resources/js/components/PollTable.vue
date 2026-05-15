<script setup>
  import { ref } from 'vue';
  import { usePollStore } from '@/stores/usePollStore';

  const { polls, deletePoll } = usePollStore();

  // CHANGEMENT :
  // Permet de mémoriser l'id du dernier sondage dont le lien a été copié.
  // On l'utilise pour afficher un retour utilisateur : "Lien copié".
  const copiedPollId = ref(null);

  async function delPoll(id) {
    console.log('delete Poll ID:', id);
    await deletePoll(id);
  }

  // CHANGEMENT :
  // Construit le lien public du sondage grâce à son secret_token,
  // puis copie ce lien dans le presse-papiers.
  async function copyShareLink(poll) {
    const shareUrl = `${window.location.origin}/polls/${poll.secret_token}`;

    await navigator.clipboard.writeText(shareUrl);

    copiedPollId.value = poll.id;

    setTimeout(() => {
      copiedPollId.value = null;
    }, 2000);
  }
</script>

<template>
  <!-- CHANGEMENT :
       Si aucun sondage n'existe, on affiche un état vide plus propre. -->
  <div v-if="polls.length === 0" class="empty-state">
    <h2>Aucun sondage</h2>
    <p>Commencez par créer votre premier sondage.</p>
  </div>

  <!-- CHANGEMENT :
       Le tableau est maintenant dans une carte centrée,
       comme le formulaire de création / édition. -->
  <section v-else class="table-card">
    <div class="table-header">
      <div>
        <h2>Mes sondages</h2>
        <p>Retrouvez ici tous vos sondages.</p>
      </div>
    </div>

    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <!-- CHANGEMENT :
                 La colonne Actions est gardée en première position,
                 car elle contient les boutons Modifier / Copier / Supprimer. -->
            <th>Actions</th>
            <th>ID</th>
            <th>Titre</th>
            <th>Question</th>

            <!-- CHANGEMENT :
                 Nouvelle colonne pour savoir si les résultats sont publics ou privés. -->
            <th>Résultats</th>

            <!-- CHANGEMENT :
                 "Brouillon" devient "Statut", car la colonne affiche soit Brouillon, soit Lancé. -->
            <th>Statut</th>
            <th>Début</th>
            <th>Fin</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="poll in polls" :key="poll.id">
            <td class="actions">
              <!-- CHANGEMENT :
                   Si le sondage est en brouillon, Modifier reste un vrai lien cliquable. -->
              <a
                v-if="poll.is_draft"
                class="edit-link"
                :href="'/polls/dashboard/' + poll.id + '/edit'"
              >
                Modifier
              </a>

              <!-- CHANGEMENT :
                   Si le sondage est lancé, on garde le bouton visible,
                   mais on le désactive visuellement et fonctionnellement. -->
              <span
                v-else
                class="edit-link-disabled"
                title="Un sondage lancé ne peut plus être modifié"
              >
                Modifier
              </span>

              <!-- CHANGEMENT :
                   Bouton permettant de copier le lien de partage du sondage. -->
              <button
                class="share-button"
                type="button"
                @click="copyShareLink(poll)"
              >
                {{ copiedPollId === poll.id ? 'Lien copié' : 'Copier le lien' }}
              </button>

              <button class="delete-button" @click="delPoll(poll.id)">
                Supprimer
              </button>
            </td>

            <td>{{ poll.id }}</td>
            <td>{{ poll.title || '-' }}</td>
            <td>{{ poll.question }}</td>

            <!-- CHANGEMENT :
                 Affiche la visibilité des résultats.
                 Public = les résultats sont visibles via le lien.
                 Privé = seuls les propriétaires peuvent les voir. -->
            <td>
              <span
                class="badge"
                :class="poll.results_public ? 'badge-green' : 'badge-red'"
              >
                {{ poll.results_public ? 'Public' : 'Privé' }}
              </span>
            </td>

            <td>
              <!-- CHANGEMENT :
                   Badge visuel pour rendre l'état plus lisible. -->
              <span :class="poll.is_draft ? 'badge badge-draft' : 'badge badge-live'">
                {{ poll.is_draft ? 'Brouillon' : 'Lancé' }}
              </span>
            </td>

            <td>{{ poll.started_at || '-' }}</td>

            <!-- CHANGEMENT :
                 Si le sondage n'a pas de date de fin,
                 on affiche "Aucune limite" au lieu d'un simple tiret. -->
            <td>{{ poll.ends_at || 'Aucune limite' }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<style scoped>
  .table-card,
  .empty-state {
    max-width: 1100px;
    margin: 0 auto 2rem;
    padding: 1.5rem;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 0.75rem;
  }

  .table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }

  .table-header h2,
  .empty-state h2 {
    margin: 0;
    font-size: 1.4rem;
  }

  .table-header p,
  .empty-state p {
    margin: 0.25rem 0 0;
    color: #64748b;
  }

  .table-wrapper {
    overflow-x: auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
  }

  th {
    padding: 0.75rem;
    color: #334155;
    background-color: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
  }

  td {
    padding: 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
  }

  tr:last-child td {
    border-bottom: none;
  }

  /* CHANGEMENT :
     Les boutons d'action sont maintenant en colonne,
     avec une largeur identique pour une disposition plus propre. */
  .actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-start;
  }

  .edit-link,
  .edit-link-disabled,
  .share-button,
  .delete-button {
    display: inline-block;
    width: 160px;
    padding: 0.45rem 0.7rem;
    border: none;
    border-radius: 0.4rem;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
  }

  .edit-link,
  .share-button,
  .delete-button {
    cursor: pointer;
  }

  .edit-link {
    color: white;
    background-color: #2563eb;
  }

  .edit-link:hover {
    background-color: #1d4ed8;
  }

  .edit-link-disabled {
    color: #64748b;
    background-color: #e2e8f0;
    cursor: not-allowed;
  }

  .share-button {
    color: #334155;
    background-color: white;
    border: 1px solid #cbd5e1;
  }

  .share-button:hover {
    background-color: #f8fafc;
  }

  .delete-button {
    color: #b91c1c;
    background-color: white;
    border: 1px solid #fecaca;
  }

  .delete-button:hover {
    background-color: #fef2f2;
  }

  .badge {
    display: inline-block;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .badge-draft {
    color: #92400e;
    background-color: #fef3c7;
  }

  .badge-live {
    color: #166534;
    background-color: #dcfce7;
  }

  .badge-green {
    color: #166534;
    background-color: #dcfce7;
  }

  .badge-red {
    color: #991b1b;
    background-color: #fee2e2;
  }

  /* CHANGEMENT :
   Version mobile du tableau.
   Sur petit écran, chaque ligne devient une carte verticale. */
@media (max-width: 700px) {
  .table-card,
  .empty-state {
    padding: 1rem;
    margin: 0 1rem 2rem;
  }

  table,
  thead,
  tbody,
  tr,
  th,
  td {
    display: block;
  }

  thead {
    display: none;
  }

  tr {
    padding: 1rem 0;
    border-bottom: 1px solid #e2e8f0;
  }

  td {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.5rem 0;
    border-bottom: none;
  }

  td::before {
    font-weight: 700;
    color: #64748b;
  }

  td:nth-child(1)::before {
    content: "Actions";
  }

  td:nth-child(2)::before {
    content: "ID";
  }

  td:nth-child(3)::before {
    content: "Titre";
  }

  td:nth-child(4)::before {
    content: "Question";
  }

  td:nth-child(5)::before {
    content: "Résultats";
  }

  td:nth-child(6)::before {
    content: "Statut";
  }

  td:nth-child(7)::before {
    content: "Début";
  }

  td:nth-child(8)::before {
    content: "Fin";
  }

  .actions {
    align-items: stretch;
  }

  .actions::before {
    content: "Actions";
    font-weight: 700;
    color: #64748b;
    margin-bottom: 0.5rem;
  }

  .edit-link,
  .edit-link-disabled,
  .share-button,
  .delete-button {
    width: 100%;
  }
}
</style>

