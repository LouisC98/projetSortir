<?php

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AdminControllerTest extends WebTestCase
{
    private function loginAsAdmin($client): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $adminUser = $userRepository->findOneByEmail('admin@test.com');
        $client->loginUser($adminUser);
    }

    private function createCsvFile(string $content): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'test_csv');
        file_put_contents($filePath, $content);
        return $filePath;
    }

    // ========== TESTS D'IMPORT VALIDE ==========

    public function testImportUsersWithValidCsv(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "import1@example.com,importuser1,Dupont,Jean,0123456789,Nantes\n";
        $csvContent .= "import2@example.com,importuser2,Martin,Marie,0987654321,Rennes\n";

        $filePath = $this->createCsvFile($csvContent);
        $uploadedFile = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);

        $crawler = $client->request('GET', '/admin/users/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['user_import_form[csv_file]']->setValue($uploadedFile);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/users/import');

        $client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', '2 utilisateurs');

        unlink($filePath);
    }

    // ========== TESTS D'ERREURS  ==========

    public function testImportUsersWithInvalidEmail(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "invalid-email,importuser3,Test,User,0123456789,Nantes\n";

        $filePath = $this->createCsvFile($csvContent);
        $uploadedFile = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);

        $crawler = $client->request('GET', '/admin/users/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['user_import_form[csv_file]']->setValue($uploadedFile);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/users/import');
        $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');

        unlink($filePath);
    }

    public function testImportUsersWithInvalidSite(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "test@example.com,testuser,Test,User,0123456789,SiteInexistant\n";

        $filePath = $this->createCsvFile($csvContent);
        $uploadedFile = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);

        $crawler = $client->request('GET', '/admin/users/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['user_import_form[csv_file]']->setValue($uploadedFile);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/users/import');
        $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');

        unlink($filePath);
    }

    public function testImportUsersWithMultipleErrors(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "invalid-email,importuser3,Test,User,0123456789,Nantes\n";
        $csvContent .= "import4@example.com,importuser4,Test,User,0123456789,SiteInconnu\n";

        $filePath = $this->createCsvFile($csvContent);
        $uploadedFile = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);

        $crawler = $client->request('GET', '/admin/users/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['user_import_form[csv_file]']->setValue($uploadedFile);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/users/import');
        $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');

        unlink($filePath);
    }

    public function testImportUsersWithDuplicateEmail(): void
    {
        $client = static::createClient();
        $this->loginAsAdmin($client);

        $csvContent = "email,pseudo,nom,prenom,telephone,site_nom\n";
        $csvContent .= "admin@test.com,newpseudo,Test,User,0123456789,Nantes\n";

        $filePath = $this->createCsvFile($csvContent);
        $uploadedFile = new UploadedFile($filePath, 'import.csv', 'text/csv', null, true);

        $crawler = $client->request('GET', '/admin/users/import');
        $form = $crawler->selectButton('Importer')->form();
        $form['user_import_form[csv_file]']->setValue($uploadedFile);

        $client->submit($form);

        $this->assertResponseRedirects('/admin/users/import');
        $client->followRedirect();

        $this->assertSelectorExists('.alert-danger');

        unlink($filePath);
    }
}