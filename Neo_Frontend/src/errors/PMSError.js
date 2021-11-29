export default class PMSError extends Error {
  constructor(code, message) {
    super();
    this.name = this.constructor.name;
    this.message = message;
    this.errorCode = code;
  }
}
