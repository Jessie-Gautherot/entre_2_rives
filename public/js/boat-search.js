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
            displayMessage('Impossible de charger les bateaux disponibles.');
            return;
        }

        const boatModels = await response.json();

        boatResults.replaceChildren();

        if (boatModels.length === 0) {
            displayMessage("Aucun bateau n'est disponible pour cette recherche.");
            return;
        }

        boatModels.forEach((boatModel) => {
            const column = document.createElement('div');
            column.classList.add('col-12', 'col-lg-4');

            const article = document.createElement('article');
            article.classList.add('card', 'h-100', 'shadow-sm');

            const image = document.createElement('img');
            image.src = `/${boatModel.mainImage}`;
            image.alt = `Bateau ${boatModel.name}`;
            image.classList.add('card-img-top', 'boat-catalogue-image');

            const cardBody = document.createElement('div');
            cardBody.classList.add('card-body', 'text-center');

            const title = document.createElement('h2');
            title.classList.add('card-title', 'boat-catalogue-card-title');
            title.textContent = boatModel.name;

            const capacity = document.createElement('p');
            capacity.classList.add('boat-catalogue-capacity');
            capacity.textContent = `Jusqu'à ${boatModel.capacity} personnes`;

            const description = document.createElement('p');
            description.classList.add('card-text');
            description.textContent = boatModel.description;

            const link = document.createElement('a');
            link.classList.add('btn', 'btn-primary-custom');
            link.href = `/bateaux/${boatModel.slug}`;
            link.textContent = 'Voir ce bateau';
            link.setAttribute(
                'aria-label',
                `Voir le bateau ${boatModel.name}`
            );

            cardBody.appendChild(title);
            cardBody.appendChild(capacity);
            cardBody.appendChild(description);
            cardBody.appendChild(link);

            article.appendChild(image);
            article.appendChild(cardBody);

            column.appendChild(article);

            boatResults.appendChild(column);
        });
    } catch (error) {
        displayMessage(
            'Une erreur est survenue lors du chargement des bateaux.'
        );
    }
});

// Displays a message in the catalogue area.
function displayMessage(message) {
    boatResults.replaceChildren();

    const paragraph = document.createElement('p');
    paragraph.classList.add('text-center');
    paragraph.textContent = message;

    boatResults.appendChild(paragraph);
}

resetButton.addEventListener('click', () => {
    boatResults.innerHTML = initialBoatResults;
});