describe('Test scenario: Inloggen', function () {

  it('Inloggen-1: Inloggen blokkeren na te veel foute pogingen', function () {
    cy.request({
      method: 'POST',
      url: 'http://localhost:8000/inloggen',
      form: true,
      body: { email: 'test@geoprofs.test', password: 'Welkom123!' },
      followRedirect: false,
    }).its('headers.location').should('include', '/dashboard');
    cy.clearCookies();

    cy.visit('http://localhost:8000/login.html');
    cy.get('#email').should('be.visible');
    cy.get('#wachtwoord').should('be.visible');

    for (let i = 1; i <= 5; i++) {
      cy.get('#rol').select('werknemer');
      cy.get('#email').clear().type('test@geoprofs.test');
      cy.get('#wachtwoord').clear().type('fout' + i);
      cy.get('button[type="submit"]').click();

      cy.url().should('eq', 'http://localhost:8000/login.html');
    }

    cy.get('#rol').select('werknemer');
    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#wachtwoord').clear().type('fout6');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/login.html');

    cy.get('#rol').select('werknemer');
    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#wachtwoord').clear().type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/login.html');

    cy.wait(60000);

    cy.get('#rol').select('werknemer');
    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#wachtwoord').clear().type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/dashboard');
  });

  it('Inloggen-2: Inloggen met SQL-injectie', function () {
    cy.visit('http://localhost:8000/login.html');
    cy.get('#email').should('be.visible');

    cy.get('#rol').select('werknemer');
    cy.get('#email').type("' OR '1'='1' --");
    cy.get('#wachtwoord').type('Welkom123!');
    cy.get('button[type="submit"]').click();

    cy.get('#email:invalid').should('exist');
    cy.url().should('eq', 'http://localhost:8000/login.html');

    cy.request({
      method: 'POST',
      url: 'http://localhost:8000/inloggen',
      form: true,
      body: { email: "' OR '1'='1' --", password: 'Welkom123!' },
      followRedirect: false,
    }).its('headers.location').should('include', '/login.html');

    cy.get('#email').clear().type('test@geoprofs.test');
    cy.get('#wachtwoord').clear().type("' OR '1'='1");
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/login.html');

    cy.visit('http://localhost:8000/dashboard');

    cy.url().should('include', '/login');

    cy.readFile('storage/logs/laravel.log').should('not.contain', 'SQLSTATE');
  });

  it('Inloggen-3: Eigen rol aanpassen via het profiel', function () {
    cy.visit('http://localhost:8000/login.html');
    cy.get('#rol').select('werknemer');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#wachtwoord').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:8000/dashboard');

    cy.visit('http://localhost:8000/settings/profile');

    cy.get('#name').should('have.value', 'Test Werknemer');
    cy.get('#email').should('have.value', 'test@geoprofs.test');

    cy.getCookie('XSRF-TOKEN').then(function (cookie) {
      cy.request({
        method: 'PATCH',
        url: 'http://localhost:8000/settings/profile',
        headers: { 'X-XSRF-TOKEN': decodeURIComponent(cookie.value) },
        body: { name: 'Test Werknemer', email: 'test@geoprofs.test', role: 'manager' },
      }).its('status').should('eq', 200);
    });

    cy.exec(`php artisan tinker --execute="echo App\\Models\\User::where('email', 'test@geoprofs.test')->value('role');"`)
      .its('stdout').should('contain', 'werknemer');
  });

  it('Inloggen-4: Zelfde reactie bij onbekend account en fout wachtwoord', function () {
    cy.visit('http://localhost:8000/login.html');
    cy.get('#email').should('be.visible');

    cy.get('#rol').select('werknemer');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#wachtwoord').type('Fout12345');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/login.html');
    cy.get('body').invoke('text').as('paginaBestaand');

    cy.get('#rol').select('werknemer');
    cy.get('#email').clear().type('bestaatniet@geoprofs.test');
    cy.get('#wachtwoord').clear().type('Fout12345');
    cy.get('button[type="submit"]').click();

    cy.url().should('eq', 'http://localhost:8000/login.html');
    cy.get('@paginaBestaand').then(function (paginaBestaand) {
      cy.get('body').invoke('text').should('eq', paginaBestaand);
    });

    cy.request({
      method: 'POST',
      url: 'http://localhost:8000/inloggen',
      form: true,
      body: { email: 'test@geoprofs.test', password: 'Fout12345' },
      followRedirect: false,
    }).its('headers.location').as('redirectBestaand');

    cy.get('@redirectBestaand').then(function (redirectBestaand) {
      cy.request({
        method: 'POST',
        url: 'http://localhost:8000/inloggen',
        form: true,
        body: { email: 'bestaatniet@geoprofs.test', password: 'Fout12345' },
        followRedirect: false,
      }).its('headers.location').should('eq', redirectBestaand);
    });
  });

  it('Inloggen-5: Sessie is ongeldig na uitloggen', function () {
    cy.visit('http://localhost:8000/login.html');
    cy.get('#rol').select('werknemer');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#wachtwoord').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:8000/dashboard');

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

    cy.request({
      url: 'http://localhost:8000/verlofsaldo',
      followRedirect: false,
      failOnStatusCode: false,
    }).its('status').should('be.oneOf', [302, 401, 403]);

    cy.visit('http://localhost:8000/verlofsaldo');

    cy.url().should('include', '/login');
  });

  it('Inloggen-6: Beveiligde paginas openen zonder in te loggen', function () {
    cy.visit('http://localhost:8000/login.html');
    cy.get('#rol').select('werknemer');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#wachtwoord').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:8000/dashboard');

    cy.visit('http://localhost:8000/verlof-aanvragen');
    cy.get('input[type="date"]').eq(0).type('2026-12-07');
    cy.get('input[type="date"]').eq(1).type('2026-12-09');
    cy.get('textarea').type('Inloggen-6');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:8000/mijn-aanvragen');

    cy.exec(`php artisan tinker --execute="echo App\\Models\\LeaveRequest::where('reason', 'Vakantie: Inloggen-6')->latest('id')->value('id');"`)
      .its('stdout').then(function (id) {
        cy.wrap(id.trim()).as('aanvraagId');
      });

    cy.get('[data-sidebar="footer"] [data-sidebar="menu-button"]').click();
    cy.contains('Log out').click();

    cy.visit('http://localhost:8000/dashboard');

    cy.url().should('include', '/login');

    cy.visit('http://localhost:8000/verlofsaldo');

    cy.url().should('include', '/login');

    cy.visit('http://localhost:8000/verlof-aanvragen');

    cy.url().should('include', '/login');

    cy.visit('http://localhost:8000/afwezigheid-inzien');

    cy.url().should('include', '/login');

    cy.get('@aanvraagId').then(function (aanvraagId) {
      cy.request({
        method: 'POST',
        url: 'http://localhost:8000/verlofaanvragen/' + aanvraagId + '/goedkeuren',
        failOnStatusCode: false,
        followRedirect: false,
      }).its('status').should('be.oneOf', [302, 401, 403, 419]);

      cy.exec(`php artisan tinker --execute="echo App\\Models\\LeaveRequest::where('id', ${aanvraagId})->value('status');"`)
        .its('stdout').should('contain', 'in_behandeling');
    });
  });

  it('Inloggen-7: Wachtwoorden zijn versleuteld opgeslagen', function () {
    cy.exec(`php artisan tinker --execute="echo App\\Models\\User::where('email', 'test@geoprofs.test')->value('password');"`)
      .its('stdout').then(function (hash) {
        cy.wrap(hash.trim()).as('hash');
      });

    cy.get('@hash').should('match', /^\$2y\$/);
    cy.get('@hash').should('not.eq', 'Welkom123!');

    cy.visit('http://localhost:8000/login.html');
    cy.get('#rol').select('werknemer');
    cy.get('#email').type('test@geoprofs.test');
    cy.get('#wachtwoord').type('Welkom123!');
    cy.get('button[type="submit"]').click();
    cy.url().should('eq', 'http://localhost:8000/dashboard');

    cy.get('@hash').then(function (hash) {
      cy.request('http://localhost:8000/dashboard').its('body').should('not.contain', hash);
    });
    cy.request('http://localhost:8000/dashboard').its('body').should('not.contain', 'remember_token');

    cy.readFile('storage/logs/laravel.log').should('not.contain', 'Welkom123!');
  });

});