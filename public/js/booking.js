const dateInput = document.getElementById('booking-date');
const modelSelect = document.getElementById('booking-model');
const rentalRateSelect = document.getElementById('booking-rental-rate');
const availabilityStatus = document.getElementById('booking-availability-status');

async function loadAvailableRentalRates() {
    const date = dateInput.value;
    const modelId = modelSelect.value;

    // Resets the rental rate select.
    rentalRateSelect.replaceChildren();

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Choisir une formule';

    rentalRateSelect.appendChild(defaultOption);

    availabilityStatus.textContent = '';

    // Waits until a date and a boat model are selected.
    if (!date || !modelId) {
        return;
    }

    try {
        // Gets the available rental rates from the server.
        const response = await fetch(
            `/reservation/formules-disponibles?date=${date}&model=${modelId}`
        );

        if (!response.ok) {
            availabilityStatus.textContent =
                'Impossible de charger les créneaux disponibles.';

            return;
        }

        const rentalRates = await response.json();

        if (rentalRates.length === 0) {
            availabilityStatus.textContent =
                'Aucun créneau disponible pour cette date.';

            return;
        }

        // Adds each available rental rate to the select.
        rentalRates.forEach((rentalRate) => {
            const option = document.createElement('option');

            option.value = rentalRate.id;

            option.textContent =
                `${rentalRate.label} — ` +
                `${rentalRate.durationHours} h — ` +
                `${rentalRate.startTime} à ${rentalRate.endTime} — ` +
                `${(rentalRate.price / 100).toFixed(2)} €`;

            rentalRateSelect.appendChild(option);
        });

        availabilityStatus.textContent =
            `${rentalRates.length} créneau(x) disponible(s).`;
    } catch (error) {
        availabilityStatus.textContent =
            'Une erreur est survenue lors du chargement des créneaux.';
    }
}

dateInput.addEventListener('change', loadAvailableRentalRates);
modelSelect.addEventListener('change', loadAvailableRentalRates);

// Loads the rental rates when the form is already pre-filled.
if (dateInput.value && modelSelect.value) {
    loadAvailableRentalRates();
}