<?php

namespace App\Controllers;

class Contact extends BaseController
{
    public function index(): string
    {
        return $this->themeService->view('page', array_merge($this->data, [
            'page' => (object) [
                'title'        => 'Contact',
                'content'      => view('contact_form'),
                'content_type' => 'html',
            ],
            'seo'  => [
                'title'       => 'Contact - ' . site_name(),
                'description' => '',
                'og_title'    => 'Contact',
                'og_description' => '',
                'og_image'    => '',
            ],
        ]));
    }

    public function send()
    {
        if (! $this->validate([
            'name'    => 'required|max_length[100]',
            'email'   => 'required|valid_email',
            'message' => 'required|min_length[10]|max_length[5000]',
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email = \Config\Services::email();
        $email->setFrom(setting('Email.fromEmail') ?? 'no-reply@example.com', setting('Email.fromName') ?? site_name());
        $email->setTo(setting('App.siteEmail') ?? 'admin@example.com');
        $email->setReplyTo($this->request->getPost('email'), $this->request->getPost('name'));
        $email->setSubject('Contact Form: ' . site_name());
        $email->setMessage(
            "Name: " . esc($this->request->getPost('name')) . "\n" .
            "Email: " . esc($this->request->getPost('email')) . "\n\n" .
            esc($this->request->getPost('message'))
        );
        $email->send();

        return redirect()->to('/contact')->with('success', 'Your message has been sent!');
    }
}
