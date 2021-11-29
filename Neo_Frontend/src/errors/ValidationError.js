export default class ValidationError extends Error {
  constructor(code, message, errors) {
    super();
    this.name = this.constructor.name;
    this.message = message;
    this.code = code;
    this.errors = errors || [];
  }

  fields() {
    return Object.keys(this.errors);
  }

  errorsFor(field) {
    return this.errors[field] || [];
  }

  firstErrorFor(field) {
    return Array.from(this.errors[field] || []).shift();
  }

  hasErrorsFor(field) {
    return this.errors[field] != null;
  }
}
