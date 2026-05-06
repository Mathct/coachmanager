import { startStimulusApp } from '@symfony/stimulus-bundle';
import CompositionFieldController from './controllers/composition_field_controller.js';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('composition-field', CompositionFieldController);
