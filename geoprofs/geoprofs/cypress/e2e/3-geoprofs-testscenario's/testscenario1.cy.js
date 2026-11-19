describe('Test scenario: Inloggen', function () {

  it('Inloggen-1: Inloggen blokkeren na te veel foute pogingen', function () {
    cy.visit('http://geoprofs.test/login');
    cy.get('#email').should('be.visible');
    cy.get('#password').should('be.visible');

    for (let i = 1; i <= 5; i++) {
      cy.get('#email').clear().type('test@geoprofs.test');
      cy.get('#password').clear().type('fout' + i);
      cy.get('button[type="submit"]').click();

      cy.get('p.text-red-600').should('be.visible');
      cy.url().should('include', '/login');
    }

    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#password').clear().type('fout6');
    cy.get('button[type="submit"]').click();

    cy.get('p.text-red-600').should('contain', 'Too many');

    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#password').clear().type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.get('p.text-red-600').should('contain', 'Too many');
    cy.url().should('include', '/login');

    cy.wait(60000);

    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#password').clear().type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://geoprofs.test/dashboard');
  });

  it('Inloggen-2: Inloggen met SQL-injectie', function () {
    cy.visit('http://geoprofs.test/login');
    cy.get('#email').should('be.visible');

    cy.get('#email').type("' OR '1'='1' --");
    cy.get('#password').type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.get('#email:invalid').should('exist');
    cy.url().should('include', '/login');

    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#password').clear().type("' OR '1'='1");
    cy.get('button[type="submit"]').click();

    cy.get('p.text-red-600').should('be.visible');
    cy.url().should('include', '/login');

    cy.readFile('storage/logs/laravel.log').should('not.contain', 'SQLSTATE');
  });

  it('Inloggen-3: Eigen rol aanpassen via het profiel', function () {
    cy.visit('http://geoprofs.test/login');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#password').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://geoprofs.test/dashboard');

    cy.visit('http://geoprofs.test/settings/profile');

    cy.get('#name').should('be.visible');
    cy.get('#email').should('have.value', 'test@geoprofs.test');

    cy.getCookie('XSRF-TOKEN').then(function (cookie) {
      cy.request({
        method: 'PATCH',
        url: 'http://geoprofs.test/settings/profile',
        headers: { 'X-XSRF-TOKEN': decodeURIComponent(cookie.value) },
        body: { name: 'Sanne', email: 'test@geoprofs.test', role: 'manager' },
      }).its('status').should('eq', 200);
    });

    cy.exec(`php artisan tinker --execute="echo App\\Models\\User::where('email', 'test@geoprofs.test')->first()->role;"`)
      .its('stdout').should('contain', 'werknemer');
  });

  it('Inloggen-4: Zelfde foutmelding bij onbekend account en fout wachtwoord', function () {
    cy.visit('http://geoprofs.test/login');
    cy.get('#email').should('be.visible');

    cy.get('#email').type('test@geoprofs.test');
    cy.get('#password').type('Fout12345');
    cy.get('button[type="submit"]').click();

    cy.get('p.text-red-600').should('be.visible');
    cy.get('p.text-red-600').invoke('text').as('meldingBestaand');

    cy.get('#email').clear().type('bestaatniet@geoprofs.test');
    cy.get('#password').clear().type('Fout12345');
    cy.get('button[type="submit"]').click();

    cy.get('@meldingBestaand').then(function (meldingBestaand) {
      cy.get('p.text-red-600').should('have.text', meldingBestaand);
    });
  });

  it('Inloggen-5: Sessie is ongeldig na uitloggen', function () {
    cy.visit('http://geoprofs.test/login');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#password').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://geoprofs.test/dashboard');

    cy.getCookie('laravel_session').should('exist');
    cy.getCookie('laravel_session').then(function (cookie) {
      cy.wrap(cookie.value).as('oudeSessie');
    });

    cy.get('[data-sidebar="footer"] [data-sidebar="menu-button"]').click();
    cy.contains('Log out').click();

    cy.url().should('not.include', '/dashboard');

    cy.go('back');
    cy.reload();

    cy.url().should('include', '/login');

    cy.get('@oudeSessie').then(function (oudeSessie) {
      cy.setCookie('laravel_session', oudeSessie);
    });
    cy.visit('http://geoprofs.test/verlofsaldo');

    cy.url().should('include', '/login');
  });

  it('Inloggen-6: Beveiligde paginas openen zonder in te loggen', function () {
    cy.visit('http://geoprofs.test/dashboard');

    cy.url().should('include', '/login');
    cy.get('#email').should('be.visible');

    cy.visit('http://geoprofs.test/verlofsaldo');

    cy.url().should('include', '/login');
    cy.get('#email').should('be.visible');

    cy.request({
      method: 'POST',
      url: 'http://geoprofs.test/verlofaanvragen/1/goedkeuren',
      failOnStatusCode: false,
      followRedirect: false,
    }).its('status').should('be.oneOf', [302, 401, 403, 419]);

    cy.exec(`php artisan tinker --execute="echo App\\Models\\LeaveRequest::find(1)->status;"`)
      .its('stdout').should('contain', 'in_behandeling');
  });

  it('Inloggen-7: Wachtwoorden zijn versleuteld opgeslagen', function () {
    cy.exec(`php artisan tinker --execute="echo App\\Models\\User::where('email', 'test@geoprofs.test')->first()->getAttributes()['password'];"`)
      .its('stdout').should('match', /^\$2y\$/);

    cy.exec(`php artisan tinker --execute="echo App\\Models\\User::where('email', 'test@geoprofs.test')->first()->getAttributes()['password'];"`)
      .its('stdout').as('hash');

    cy.visit('http://geoprofs.test/login');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#password').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://geoprofs.test/dashboard');

    cy.get('@hash').then(function (hash) {
      cy.request('http://geoprofs.test/dashboard').its('body').should('not.contain', hash.trim());
    });
    cy.request('http://geoprofs.test/dashboard').its('body').should('not.contain', 'remember_token');

    cy.readFile('storage/logs/laravel.log').should('not.contain', 'Welkom123!');
  });

});
