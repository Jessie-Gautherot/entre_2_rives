const searchForm = document.getElementById('boat-search-form');
const boatResults = document.getElementById('boat-results');
const resetButton = document.getElementById('boat-search-reset');
// Saves the initial catalogue to restore it after reset.
const initialBoatResults = boatResults.innerHTML;
// Updates the catalogue with the search results.
searchForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const date = document.getElementById('search-date').value;
    const passengerCount = document.getElementById('search-passenger-count').value;

    try {
        const response = await fetch(
            `/bateaux/recherche?date=${date}&passengerCount=${passengerCount}`
        );

        if (!response.ok) {
            boatResults.innerHTML = `
                <p class="text-center">
                    Impossible de charger les bateaux disponibles.
                </p>
            `;

            return;
        }

        const boatModels = await response.json();

        boatResults.innerHTML = '';

        if (boatModels.length === 0) {
            boatResults.innerHTML = `
                <p class="text-center">
                    Aucun bateau n'est disponible pour cette recherche.
                </p>
            `;

            return;
        }

        boatModels.forEach((boatModel) => {
            boatResults.innerHTML += `
                <div class="col-12 col-lg-4">
                    <article class="card h-100 shadow-sm">
                        <img
                            src="/${boatModel.mainImage}"
                            alt="Bateau ${boatModel.name}"
                            class="card-img-top boat-catalogue-image"
                        >

                        <div class="card-body text-center">
                            <h2 class="card-title boat-catalogue-card-title">
                                ${boatModel.name}
                            </h2>

                            <p class="boat-catalogue-capacity">
                                Jusqu'à ${boatModel.capacity} personnes
                            </p>

                            <p class="card-text">
                                ${boatModel.description}
                            </p>

                            <a
                                class="btn btn-primary-custom"
                                href="/bateaux/${boatModel.slug}"
                                aria-label="Voir le bateau ${boatModel.name}"
                            >
                                Voir ce bateau
                            </a>
                        </div>
                    </article>
                </div>
            `;
        });
    } catch (error) {
        boatResults.innerHTML = `
            <p class="text-center">
                Une erreur est survenue lors du chargement des bateaux.
            </p>
        `;
    }
});

resetButton.addEventListener('click', () => {
    boatResults.innerHTML = initialBoatResults;
});