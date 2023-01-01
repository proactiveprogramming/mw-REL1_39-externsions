## wmd.rb
## Jekyll plugin enabling Working Markdown content in Jekyll websites.
## enable by placing (I recomnent symbolic linking) this file in the
## Jekyll site directory's _plugins directory and following further
## instructions at
## http://lalashan.mcmaster.ca/theobio/projects/index.php/Working_Markup

## This is tested with Ruby 2.3.1 and Jekyll 3.1.6

# make sure this is set to the path where wmd/wmd.php can be found
WW_DIR = '/usr/local/src/workingwiki'

# make sure this is the path to a directory where working files are to
# be created.  It should be in the Jekyll source directory, because working
# files need to be published along with the site's html.
# If set to nil, it will be set to wmd_files/ within the site's base 
# source directory.
WMD_CACHE_DIR = nil

require 'shellwords'

def pipe_text_through( text, command )
	#puts command
	# todo: signals need to be passed on to subprocess so that Ctrl-C
	# will kill the make operation, not just the jekyll parsing.
	open('|'+command, 'w+') do |subprocess|
		subprocess.write( text )
		subprocess.close_write
		return subprocess.read
	end
end

module Jekyll
	class WMDGenerator < Jekyll::Generator
		safe true
		priority :low
		@project_lookup = Hash.new
		@wmdCacheDir = ::WMD_CACHE_DIR
		@sourceDir = nil
		
		def self.record_project( title, value )
			@project_lookup[title] = value
		end

		def self.lookup_project( title )
			@project_lookup[title]
		end

		def self.setSourceDir( dir )
			@sourceDir = dir
		end

		def self.sourceDir
			@sourceDir
		end

		def self.setCacheDir( dir )
			@wmdCacheDir = dir
		end

		def self.cacheDir
			@wmdCacheDir
		end

		def initialize(config)
			config['convert_wmd'] ||= true
		end

		def generate(site)
			@site = site
			if ( WMDGenerator.sourceDir() == nil )
				WMDGenerator.setSourceDir( site.source )
			end
			if ( WMDGenerator.cacheDir() == nil )
				WMDGenerator.setCacheDir( "#{site.source}/wmd_files" )
			end
			FileUtils.rm_f( WMDGenerator.cacheDir() + '/.workingwiki/.wmd.data' )
			site.posts.docs.each do |post|
				if post.basename.match(/\.wmd$/)
					#puts 'post ' + post.basename
					convertPost post
				end
			end
			site.pages.each do |page|
				if page.name.match(/\.wmd$/)
					#puts 'page ' + page.name
					convertPage page
				end
			end
		end

		def convertText(text, title, projectname, mtime, extra_args)
			cacheDir = WMDGenerator.cacheDir()
			WMDGenerator.record_project(title, projectname)
			pipe_text_through( text, "php #{::WW_DIR}/wmd/wmd.php --pre --title=#{title.shellescape} --default-project-name=#{projectname.shellescape} --cache-dir=#{cacheDir.shellescape} --data-store=.wmd.data --modification-time=#{mtime} --process-inline-math#{extra_args}" )
		end

		def convertPost(post)
			path = post.relative_path
			puts "convertPost: #{path} #{post.data['slug']} #{post.extname}"
			post.basename.gsub!(/\.wmd$/, '')
			slug = post.data['slug']
			slug.gsub!(/\.wmd$/, '')
			ext = slug.match(/\..*?$/)[0]
			post.extname.gsub!( /\.wmd$/, ext )
			slug.gsub!(/\..*?$/, '')
			post.data['slug'] = slug
			post.data['ext'] = ext
			#puts "Calling wmd.php --pre on #{path}"
			project = (post.data.has_key?('wmd_project') ? post['wmd_project'] : post.basename)
			# TODO: modification time not tested
			mtime = File.mtime( path )
			# TODO: prerequisite projects code not tested
			extra_args = ''
			if post.data.has_key?('wmd_prerequisite_projects')
			       extra_args += ' --prerequisite-projects=' + JSON.generate(post['wmd_prerequisite_projects']).shellescape
			end
			post.content = convertText( post.content, path, project, mtime.to_i, extra_args )
		end
	
		def convertPage(page)
			# path is apparently modified by changing page.name,
			# so capture it before
			path = WMDGenerator.sourceDir() + '/' + page['path']
			page.ext = page.basename.match(/\..*?$/)[0]
			page.basename.gsub!(/\..*?$/, '')
			project = (page.data.has_key?('wmd_project') ? page['wmd_project'] : page.basename)
			mtime = File.mtime( path )
			extra_args = ''
			if page.data.has_key?('wmd_prerequisite_projects')
				extra_args += ' --prerequisite-projects=' + JSON.generate(page['wmd_prerequisite_projects']).shellescape
			end
			#puts "Calling wmd.php --pre on '#{page['title']}'"
			page.content = convertText( page.content, page.path, project, mtime.to_i, extra_args )
		end
	end

	module WMDFilter
		def wmd_postprocess(text)
			if ! text.match(/UNIQ/)
				return text
			end
			title = @context.registers[:page]['title']
			path = @context.registers[:page]['path']
			#project = WMDGenerator.lookup_project(title)
			project = WMDGenerator.lookup_project(path)
			baseurl = @context.registers[:site].baseurl
			wmdCacheDir = WMDGenerator.cacheDir()
			extra_args = ''
			extra_args += ' --persistent-data-store'
			enable_make = 'true'
			if @context.registers[:site].config.has_key?('wmd_enable_make')
				enable_make = @context.registers[:site].config['wmd_enable_make']
			end
			if @context.registers[:page].has_key?('wmd_enable_make')
				enable_make = @context.registers[:page]['wmd_enable_make']
			end
			if @context.registers[:site].config.has_key?('wmd_make_page')
				make_page = @context.registers[:site].config['wmd_make_page']
				make_page.gsub!(/\.wmd$/, '')
				if ! path.eql? make_page
					enable_make = 'false'
				end
			end
			if @context.registers[:site].config.has_key?('wmd_make_single_file')
				make_sf = @context.registers[:site].config['wmd_make_single_file']
				extra_args += " --make-single-file=#{make_sf.shellescape}"
			end
			extra_args += " --enable-make=#{enable_make}"
			text = pipe_text_through( text, "php #{::WW_DIR}/wmd/wmd.php --post --title=#{path.shellescape} --default-project-name=#{project.shellescape} --cache-dir=#{wmdCacheDir.shellescape} --data-store=.wmd.data --project-file-base-url=#{baseurl.shellescape}/wmd_files#{extra_args}" )
			if Dir.exists?( "wmd_files/#{project}" )
				# TODO: calling this a second time on same
				# directory doesn't redo it
				# TODO: does it work in more recent jekyll?
				@context.registers[:site].reader.read_directories( "wmd_files/#{project}" )
			end
			return text
		end
	end
end

Liquid::Template.register_filter(Jekyll::WMDFilter)
