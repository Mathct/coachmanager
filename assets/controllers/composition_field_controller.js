import { Controller } from '@hotwired/stimulus';

const FORMATIONS = {
    '4-3-3': [
        { role: 'GK', x: 50, y: 90 },
        { role: 'LB', x: 16, y: 57 },
        { role: 'LCB', x: 38, y: 59 },
        { role: 'RCB', x: 62, y: 59 },
        { role: 'RB', x: 84, y: 57 },
        { role: 'LCM', x: 28, y: 39 },
        { role: 'CM', x: 50, y: 35 },
        { role: 'RCM', x: 72, y: 39 },
        { role: 'LW', x: 20, y: 14 },
        { role: 'ST', x: 50, y: 10 },
        { role: 'RW', x: 80, y: 14 },
    ],
    '4-2-3-1': [
        { role: 'GK', x: 50, y: 90 },
        { role: 'LB', x: 16, y: 57 },
        { role: 'LCB', x: 38, y: 59 },
        { role: 'RCB', x: 62, y: 59 },
        { role: 'RB', x: 84, y: 57 },
        { role: 'DM', x: 40, y: 43 },
        { role: 'DM', x: 60, y: 43 },
        { role: 'LW', x: 20, y: 22 },
        { role: 'CAM', x: 50, y: 24 },
        { role: 'RW', x: 80, y: 22 },
        { role: 'ST', x: 50, y: 10 },
    ],
    '4-4-2': [
        { role: 'GK', x: 50, y: 90 },
        { role: 'LB', x: 16, y: 57 },
        { role: 'LCB', x: 38, y: 59 },
        { role: 'RCB', x: 62, y: 59 },
        { role: 'RB', x: 84, y: 57 },
        { role: 'LM', x: 16, y: 34 },
        { role: 'LCM', x: 38, y: 34 },
        { role: 'RCM', x: 62, y: 34 },
        { role: 'RM', x: 84, y: 34 },
        { role: 'ST', x: 42, y: 12 },
        { role: 'ST', x: 58, y: 12 },
    ],
    '3-5-2': [
        { role: 'GK', x: 50, y: 90 },
        { role: 'LCB', x: 30, y: 59 },
        { role: 'CB', x: 50, y: 61 },
        { role: 'RCB', x: 70, y: 59 },
        { role: 'LWB', x: 14, y: 36 },
        { role: 'LCM', x: 36, y: 36 },
        { role: 'CM', x: 50, y: 30 },
        { role: 'RCM', x: 64, y: 36 },
        { role: 'RWB', x: 86, y: 36 },
        { role: 'ST', x: 42, y: 12 },
        { role: 'ST', x: 58, y: 12 },
    ],
};

const POSITION_ALIASES = {
    CB: ['LCB', 'RCB', 'CB'],
    LCB: ['LCB', 'CB'],
    RCB: ['RCB', 'CB'],
    LB: ['LB', 'LWB'],
    RB: ['RB', 'RWB'],
    LWB: ['LWB', 'LB'],
    RWB: ['RWB', 'RB'],
    CM: ['CM', 'LCM', 'RCM', 'DM', 'CAM'],
    LCM: ['LCM', 'CM', 'LM'],
    RCM: ['RCM', 'CM', 'RM'],
    DM: ['DM', 'CM'],
    CAM: ['CAM', 'CM', 'ST'],
    LM: ['LM', 'LW', 'LCM'],
    RM: ['RM', 'RW', 'RCM'],
    LW: ['LW', 'LM', 'ST'],
    RW: ['RW', 'RM', 'ST'],
    ST: ['ST', 'CAM', 'LW', 'RW'],
    GK: ['GK'],
};

export default class extends Controller {
    static targets = ['formation', 'row', 'board', 'counter'];
    static values = {
        readonly: { type: Boolean, default: false },
    };

    connect() {
        this.dragState = null;
        this.previousFormation = this.formationTarget.value;
        this.positionCatalog = this.buildPositionCatalog();
        this.refresh();
    }

    refresh() {
        const formation = this.formationTarget.value;
        const formationSlots = FORMATIONS[formation] || FORMATIONS['4-3-3'];
        const allowedRoles = [...new Set(formationSlots.map((slot) => slot.role))];
        const starters = [];

        this.rowTargets.forEach((row) => {
            const status = row.querySelector('[data-role="status"]')?.value;
            const isPlaced = row.querySelector('[data-role="placed"]')?.value === '1';
            this.updatePositionChoices(row, allowedRoles);
            row.classList.toggle('cm-row-titulaire', status === 'titulaire');
            row.classList.toggle('cm-row-remplacant', status === 'remplacant');
            row.classList.toggle('cm-row-absent', status === 'absent');

            if (isPlaced) {
                starters.push({
                    playerId: row.dataset.playerId || '',
                    number: row.querySelector('[data-role="number"]')?.value || '-',
                    name: row.dataset.playerName || '',
                    position: row.querySelector('[data-role="position"]')?.value || '?',
                    coordX: row.querySelector('[data-role="coord-x"]')?.value || '',
                    coordY: row.querySelector('[data-role="coord-y"]')?.value || '',
                });
            }
        });

        if (this.hasCounterTarget) {
            this.counterTarget.textContent = `${starters.length}/11 titulaires`;
        }

        const assignedStarters = this.assignStartersToFormation(starters, formationSlots);

        const markers = formationSlots
            .map((slot) => `
                <div class="cm-slot-marker" style="left:${slot.x}%;top:${slot.y}%;">
                    <span class="cm-slot-marker-dot"></span>
                    <span class="cm-slot-marker-label">${slot.role}</span>
                </div>
            `)
            .join('');

        const cards = assignedStarters
            .filter(Boolean)
            .map((starter, index) => {
                const hasCustomCoord = starter.coordX !== '' && starter.coordY !== '';
                const coord = hasCustomCoord
                    ? { x: Number(starter.coordX), y: Number(starter.coordY) }
                    : (formationSlots[index] || { x: 50, y: 50 });

                if (!hasCustomCoord) {
                    this.updateRowCoordinates(starter.playerId, coord.x, coord.y);
                }

                return `
                    <div
                        class="cm-player-chip ${this.readonlyValue ? 'cm-player-chip-readonly' : ''}"
                        data-player-id="${starter.playerId}"
                        style="left:${coord.x}%;top:${coord.y}%;"
                    >
                        <span class="cm-player-chip-number">${starter.number}</span>
                        <span class="cm-player-chip-name">${starter.name}</span>
                    </div>
                `;
            })
            .join('');

        this.boardTarget.innerHTML = `
            <div class="cm-slot-layer">${markers}</div>
            <div class="cm-player-layer">${cards}</div>
        `;

        if (!this.readonlyValue) {
            this.bindDragEvents();
        }
    }

    onFormationChange() {
        if (this.formationTarget.value !== this.previousFormation) {
            this.clearPitchSelection();
            this.previousFormation = this.formationTarget.value;
        }
        this.refresh();
    }

    placePlayer(event) {
        const row = event.currentTarget.closest('tr');
        if (!row) {
            return;
        }

        const position = row.querySelector('[data-role="position"]')?.value || '';
        if (!position) {
            window.alert('Definis d abord un poste pour placer ce joueur.');
            return;
        }

        const formation = this.formationTarget.value;
        const formationSlots = FORMATIONS[formation] || FORMATIONS['4-3-3'];
        const takenSlots = this.collectTakenSlots(row.dataset.playerId || '');
        const slotIndex = this.findBestSlotIndex(position, formationSlots, takenSlots);

        if (slotIndex < 0) {
            window.alert('Aucun emplacement disponible pour ce poste dans la formation choisie.');
            return;
        }

        const statusSelect = row.querySelector('[data-role="status"]');
        const slot = formationSlots[slotIndex];
        const placedInput = row.querySelector('[data-role="placed"]');
        if (placedInput) {
            placedInput.value = '1';
        }
        this.updateRowCoordinates(row.dataset.playerId || '', slot.x, slot.y);
        const slotInput = row.querySelector('[data-role="slot-index"]');
        if (slotInput) {
            slotInput.value = String(slotIndex);
        }
        this.refresh();
    }

    removePlayer(event) {
        const row = event.currentTarget.closest('tr');
        if (!row) {
            return;
        }

        const coordX = row.querySelector('[data-role="coord-x"]');
        const coordY = row.querySelector('[data-role="coord-y"]');
        const slotInput = row.querySelector('[data-role="slot-index"]');
        const placedInput = row.querySelector('[data-role="placed"]');
        if (coordX) coordX.value = '';
        if (coordY) coordY.value = '';
        if (slotInput) slotInput.value = '';
        if (placedInput) placedInput.value = '0';
        this.refresh();
    }

    bindDragEvents() {
        this.boardTarget.querySelectorAll('.cm-player-chip').forEach((chip) => {
            chip.onmousedown = (event) => this.startDrag(event, chip);
        });
    }

    startDrag(event, chip) {
        event.preventDefault();
        const boardRect = this.boardTarget.getBoundingClientRect();
        const chipRect = chip.getBoundingClientRect();
        this.dragState = {
            chip,
            playerId: chip.dataset.playerId,
            offsetX: event.clientX - chipRect.left,
            offsetY: event.clientY - chipRect.top,
            boardRect,
        };

        document.onmousemove = (moveEvent) => this.onDrag(moveEvent);
        document.onmouseup = () => this.stopDrag();
    }

    onDrag(event) {
        if (!this.dragState) {
            return;
        }

        const { boardRect, chip, offsetX, offsetY } = this.dragState;
        const chipWidth = chip.offsetWidth;
        const chipHeight = chip.offsetHeight;
        const rawX = event.clientX - boardRect.left - offsetX + chipWidth / 2;
        const rawY = event.clientY - boardRect.top - offsetY + chipHeight / 2;
        const x = Math.max(chipWidth / 2, Math.min(boardRect.width - chipWidth / 2, rawX));
        const y = Math.max(chipHeight / 2, Math.min(boardRect.height - chipHeight / 2, rawY));
        const xPercent = Math.round((x / boardRect.width) * 100);
        const yPercent = Math.round((y / boardRect.height) * 100);

        chip.style.left = `${xPercent}%`;
        chip.style.top = `${yPercent}%`;
        this.updateRowCoordinates(this.dragState.playerId, xPercent, yPercent);
    }

    stopDrag() {
        document.onmousemove = null;
        document.onmouseup = null;
        this.dragState = null;
    }

    updateRowCoordinates(playerId, x, y) {
        const row = this.rowTargets.find((item) => item.dataset.playerId === String(playerId));
        if (!row) {
            return;
        }

        const inputX = row.querySelector('[data-role="coord-x"]');
        const inputY = row.querySelector('[data-role="coord-y"]');
        if (inputX && inputY) {
            inputX.value = String(x);
            inputY.value = String(y);
        }
    }

    assignStartersToFormation(starters, formationSlots) {
        const assignedStarters = [];
        const usedSlots = new Set();

        starters.forEach((starter) => {
            if (!starter.position || starter.position === '?') {
                return;
            }
            const preferredRoles = POSITION_ALIASES[starter.position] || [starter.position];
            const exactIndex = formationSlots.findIndex(
                (slot, index) => !usedSlots.has(index) && preferredRoles.includes(slot.role)
            );
            if (exactIndex >= 0) {
                usedSlots.add(exactIndex);
                assignedStarters[exactIndex] = starter;
            }
        });

        return assignedStarters;
    }

    buildPositionCatalog() {
        const catalog = [];
        const firstSelect = this.rowTargets
            .map((row) => row.querySelector('[data-role="position"]'))
            .find((element) => element && element.tagName === 'SELECT');

        if (!firstSelect) {
            return catalog;
        }

        [...firstSelect.options].forEach((option) => {
            catalog.push({ value: option.value, label: option.textContent });
        });

        return catalog;
    }

    updatePositionChoices(row, allowedRoles) {
        const select = row.querySelector('[data-role="position"]');
        if (!select || select.tagName !== 'SELECT') {
            return;
        }

        const currentValue = select.value;
        const choices = this.positionCatalog.filter(
            (item) => item.value === '' || allowedRoles.includes(item.value)
        );
        select.innerHTML = choices
            .map((item) => `<option value="${item.value}">${item.label}</option>`)
            .join('');
        if (choices.some((item) => item.value === currentValue)) {
            select.value = currentValue;
        } else {
            select.value = '';
        }
    }

    clearPitchSelection() {
        this.rowTargets.forEach((row) => {
            const coordX = row.querySelector('[data-role="coord-x"]');
            const coordY = row.querySelector('[data-role="coord-y"]');
            const slotInput = row.querySelector('[data-role="slot-index"]');
            const placedInput = row.querySelector('[data-role="placed"]');
            if (coordX) coordX.value = '';
            if (coordY) coordY.value = '';
            if (slotInput) slotInput.value = '';
            if (placedInput) placedInput.value = '0';
        });
    }

    collectTakenSlots(excludedPlayerId) {
        const taken = new Set();
        this.rowTargets.forEach((row) => {
            if ((row.dataset.playerId || '') === String(excludedPlayerId)) {
                return;
            }
            const placed = row.querySelector('[data-role="placed"]')?.value === '1';
            if (!placed) {
                return;
            }
            const slotIndex = row.querySelector('[data-role="slot-index"]')?.value;
            if (slotIndex !== '' && slotIndex !== undefined && slotIndex !== null) {
                taken.add(Number(slotIndex));
            }
        });

        return taken;
    }

    findBestSlotIndex(position, formationSlots, takenSlots) {
        const preferredRoles = POSITION_ALIASES[position] || [position];
        for (const role of preferredRoles) {
            const index = formationSlots.findIndex((slot, i) => slot.role === role && !takenSlots.has(i));
            if (index >= 0) {
                return index;
            }
        }

        return -1;
    }

}
