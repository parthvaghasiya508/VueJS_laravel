export default class AuthError extends Error {
  constructor(code, message) {
    super();
    this.name = this.constructor.name;
    this.message = message;
    this.errorCode = code;
  }
}
