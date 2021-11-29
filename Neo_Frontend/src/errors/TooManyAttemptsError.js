import moment from 'moment';
import i18n from '@/i18n';

export default class TooManyAttemptsError extends Error {
  constructor(message, resetAt = 0) {
    super();
    this.name = this.constructor.name;
    this.message = resetAt > 0
      ? i18n.t('errors.too-many-attempts', { message, when: moment.unix(resetAt).fromNow() })
      : message;
  }
}
