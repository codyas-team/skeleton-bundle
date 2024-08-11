import { startStimulusApp } from '@symfony/stimulus-bridge';

const application = startStimulusApp(require.context('./controllers', true, /\.js$/));