const PasswordHelper = {

  generateRandomPwd(passwordLength = 16) {
    const numberChars = '0123456789';
    const upperChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const lowerChars = 'abcdefghiklmnopqrstuvwxyz';
    const specialChars = '~!@-#$';
    const allChars = numberChars + upperChars + lowerChars + specialChars;
    let randPasswordArray = Array(passwordLength);
    randPasswordArray[0] = numberChars;
    randPasswordArray[1] = upperChars;
    randPasswordArray[2] = lowerChars;
    randPasswordArray[3] = specialChars;
    randPasswordArray = randPasswordArray.fill(allChars, 4);
    return randPasswordArray.map((x) => x[Math.floor(Math.random() * x.length)]).join('');
  },
};

export default PasswordHelper;
