export class ValidationError extends Error {
    title; parameters;
    constructor(msg, title, parameters) {
        super(msg)
        this.title = title
        this.parameters = parameters
    }

    getTitle(){
        return this.title
    }

    getParameters(){
        return this.parameters
    }
}

